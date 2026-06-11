/**
 * MAIN.JS - Edunova Smart Learning System
 * Centralized JavaScript for All Pages
 */

// ============================================
// API Configuration
// ============================================
const API_BASE = window.location.origin + '/edunova/api';
const STORAGE_KEYS = {
    TOKEN: 'edunova_token',
    USER: 'edunova_user',
    ADMIN: 'edunova_admin'
};

// ============================================
// Authentication Functions
// ============================================
function getToken() {
    return localStorage.getItem(STORAGE_KEYS.TOKEN);
}

function setToken(token) {
    localStorage.setItem(STORAGE_KEYS.TOKEN, token);
}

function removeToken() {
    localStorage.removeItem(STORAGE_KEYS.TOKEN);
}

function getUser() {
    const user = localStorage.getItem(STORAGE_KEYS.USER);
    return user ? JSON.parse(user) : null;
}

function setUser(user) {
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user));
}

function removeUser() {
    localStorage.removeItem(STORAGE_KEYS.USER);
}

function isAuthenticated() {
    return !!getToken();
}

function isAdmin() {
    const user = getUser();
    return user && user.role === 'admin';
}

function logout() {
    removeToken();
    removeUser();
    window.location.href = 'login.html';
}

// ============================================
// API Request Functions
// ============================================
async function apiRequest(endpoint, options = {}) {
    const token = getToken();
    const defaultHeaders = {
        'Content-Type': 'application/json',
    };
    
    if (token) {
        defaultHeaders['Authorization'] = `Bearer ${token}`;
    }
    
    const config = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, config);
        
        if (response.status === 401) {
            removeToken();
            removeUser();
            window.location.href = 'login.html';
            return null;
        }
        
        const data = await response.json();
        return { success: response.ok, data };
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

async function apiGet(endpoint) {
    return apiRequest(endpoint, { method: 'GET' });
}

async function apiPost(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(body)
    });
}

async function apiPut(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'PUT',
        body: JSON.stringify(body)
    });
}

async function apiDelete(endpoint) {
    return apiRequest(endpoint, { method: 'DELETE' });
}

// ============================================
// File Upload Functions
// ============================================
async function uploadFile(file, endpoint, onProgress) {
    const formData = new FormData();
    formData.append('file', file);
    
    const token = getToken();
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && onProgress) {
                const percent = (e.loaded / e.total) * 100;
                onProgress(percent);
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error('Upload failed'));
            }
        });
        
        xhr.addEventListener('error', () => reject(new Error('Network error')));
        
        xhr.open('POST', `${API_BASE}${endpoint}`);
        xhr.setRequestHeader('Authorization', `Bearer ${token}`);
        xhr.send(formData);
    });
}

async function uploadProfileImage(file, onProgress) {
    return uploadFile(file, '/students/upload_profile.php', onProgress);
}

// ============================================
// UI Helper Functions
// ============================================
function showLoading(element) {
    if (element) {
        element.innerHTML = `
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>Loading...</p>
            </div>
        `;
    }
}

function hideLoading(element) {
    if (element) {
        // Clear loading content
    }
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
        <button class="alert-close">&times;</button>
    `;
    
    alertDiv.querySelector('.alert-close')?.addEventListener('click', () => {
        alertDiv.remove();
    });
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
    
    const container = document.querySelector('.content-area') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime( minutes) {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

function truncateText(text, maxLength = 100) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// ============================================
// Sidebar Functions
// ============================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

function closeSidebarOnClickOutside() {
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (window.innerWidth <= 1024 && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(event.target) && !menuToggle?.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

// ============================================
// Modal Functions
// ============================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ============================================
// Search Functions
// ============================================
function initSearch(searchInputId, itemSelector, titleSelector) {
    const searchInput = document.getElementById(searchInputId);
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const items = document.querySelectorAll(itemSelector);
        
        items.forEach(item => {
            const title = item.querySelector(titleSelector)?.innerText.toLowerCase() || '';
            if (title.includes(term)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// ============================================
// Pagination Functions
// ============================================
class Pagination {
    constructor(items, itemsPerPage = 10) {
        this.items = items;
        this.itemsPerPage = itemsPerPage;
        this.currentPage = 1;
        this.totalPages = Math.ceil(items.length / itemsPerPage);
    }
    
    getCurrentItems() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        return this.items.slice(start, end);
    }
    
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return false;
        this.currentPage = page;
        return true;
    }
    
    nextPage() {
        return this.goToPage(this.currentPage + 1);
    }
    
    prevPage() {
        return this.goToPage(this.currentPage - 1);
    }
    
    getInfo() {
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(start + this.itemsPerPage - 1, this.items.length);
        return `Showing ${start} to ${end} of ${this.items.length} items`;
    }
}

// ============================================
// Calendar Functions
// ============================================
function generateCalendar(month, year, containerId, onDateSelect) {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let html = '<div class="calendar-grid">';
    const dayNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    dayNames.forEach(day => {
        html += `<div class="calendar-day">${day}</div>`;
    });
    
    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const prevDate = new Date(year, month, -i).getDate();
        html += `<div class="calendar-date muted" data-date="${year}-${month}-${prevDate}">${prevDate}</div>`;
    }
    
    // Current month days
    const today = new Date();
    const isCurrentMonth = month === today.getMonth() && year === today.getFullYear();
    const currentDay = today.getDate();
    
    for (let i = 1; i <= daysInMonth; i++) {
        const isToday = isCurrentMonth && i === currentDay;
        html += `<div class="calendar-date ${isToday ? 'active' : ''}" data-date="${year}-${month + 1}-${i}">${i}</div>`;
    }
    
    // Next month days
    const totalCells = 42;
    const remainingCells = totalCells - (firstDay + daysInMonth);
    for (let i = 1; i <= remainingCells; i++) {
        html += `<div class="calendar-date muted" data-date="${year}-${month + 2}-${i}">${i}</div>`;
    }
    
    html += '</div>';
    
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = html;
        
        // Add click handlers
        container.querySelectorAll('.calendar-date').forEach(el => {
            el.addEventListener('click', () => {
                if (onDateSelect && !el.classList.contains('muted')) {
                    onDateSelect(el.dataset.date);
                }
            });
        });
    }
    
    return { month: monthNames[month], year };
}

// ============================================
// Chart Functions
// ============================================
function createProgressCircle(elementId, percentage) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.style.background = `conic-gradient(var(--primary) 0% ${percentage}%, #e5e7eb ${percentage}% 100%)`;
    const textElement = element.querySelector('.circle-text h2');
    if (textElement) {
        textElement.innerText = `${percentage}%`;
    }
}

// ============================================
// Initialization
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar close on outside click
    closeSidebarOnClickOutside();
    
    // Set user name if logged in
    const user = getUser();
    if (user) {
        const nameElements = document.querySelectorAll('.user-name');
        nameElements.forEach(el => {
            el.innerText = user.name || 'Student';
        });
    }
    
    // Initialize all tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.innerText = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
            
            this.addEventListener('mouseleave', () => tooltip.remove(), { once: true });
        });
    });
});

// ============================================
// Exports for global use
// ============================================
window.Edunova = {
    // Auth
    getToken, setToken, removeToken,
    getUser, setUser, removeUser,
    isAuthenticated, isAdmin, logout,
    
    // API
    apiRequest, apiGet, apiPost, apiPut, apiDelete,
    
    // Uploads
    uploadFile, uploadProfileImage,
    
    // UI
    showLoading, hideLoading, showAlert,
    formatDate, formatTime, truncateText,
    
    // Sidebar
    toggleSidebar, closeSidebarOnClickOutside,
    
    // Modal
    openModal, closeModal,
    
    // Search & Pagination
    initSearch, Pagination,
    
    // Calendar & Charts
    generateCalendar, createProgressCircle
};