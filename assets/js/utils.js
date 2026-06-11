/**
 * Edunova Utility Functions
 * Common helper functions used across the application
 */

/**
 * Show a toast notification
 * @param {string} message - Notification message
 * @param {string} type - Type of notification (success, error, info)
 * @param {number} duration - Duration in milliseconds
 */
function showToast(message, type = 'success', duration = 3000) {
  if (!message || typeof message !== 'string') {
    console.warn('Invalid toast message');
    return;
  }

  const toast = document.createElement('div');
  toast.className = 'toast';

  const iconMap = {
    'success': 'check-circle',
    'error': 'exclamation-circle',
    'info': 'info-circle'
  };

  const colorMap = {
    'success': '#22c55e',
    'error': '#ef4444',
    'info': '#333'
  };

  toast.style.background = colorMap[type] || colorMap['info'];
  toast.innerHTML = `<i class="fas fa-${iconMap[type] || iconMap['info']}"></i> ${escapeHtml(message)}`;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, duration);
}

/**
 * Escape HTML special characters to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Smooth scroll to a section
 * @param {string} sectionId - ID of the section to scroll to
 */
function scrollToSection(sectionId) {
  if (!sectionId || typeof sectionId !== 'string') {
    console.warn('Invalid section ID');
    return;
  }

  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' });
  }
}

/**
 * Get user data from localStorage
 * @returns {object|null} User data or null if not found
 */
function getUserData() {
  try {
    const userData = localStorage.getItem(Config.STORAGE.USER);
    return userData ? JSON.parse(userData) : null;
  } catch (error) {
    console.error('Error parsing user data:', error);
    return null;
  }
}

/**
 * Get authentication token from localStorage
 * @returns {string|null} Token or null if not found
 */
function getAuthToken() {
  return localStorage.getItem(Config.STORAGE.TOKEN);
}

/**
 * Check if user is authenticated
 * @returns {boolean} True if authenticated
 */
function isAuthenticated() {
  return !!getAuthToken();
}

/**
 * Clear all authentication data
 */
function clearAuthData() {
  localStorage.removeItem(Config.STORAGE.TOKEN);
  localStorage.removeItem(Config.STORAGE.USER);
  localStorage.removeItem(Config.STORAGE.ADMIN);
}

/**
 * Animate count from 0 to target value
 * @param {HTMLElement} element - DOM element to update
 * @param {number} target - Target value
 * @param {boolean} isPercentage - Whether to display as percentage
 * @param {number} duration - Animation duration in milliseconds
 */
function animateCount(element, target, isPercentage = false, duration = 1500) {
  if (!element || typeof target !== 'number') {
    console.warn('Invalid parameters for animateCount');
    return;
  }

  let current = 0;
  const increment = target / (duration / 30);
  const startTime = Date.now();

  const animate = () => {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);
    current = target * progress;

    const displayValue = Math.floor(current);
    if (isPercentage) {
      element.innerText = displayValue + '%';
    } else {
      element.innerText = displayValue.toLocaleString();
    }

    if (progress < 1) {
      requestAnimationFrame(animate);
    }
  };

  animate();
}

/**
 * Format date to readable format
 * @param {string|Date} date - Date to format
 * @returns {string} Formatted date
 */
function formatDate(date) {
  try {
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch (error) {
    console.error('Error formatting date:', error);
    return '';
  }
}

/**
 * Debounce function to limit function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 300) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Check if element is in viewport
 * @param {HTMLElement} element - Element to check
 * @returns {boolean} True if element is visible
 */
function isElementInViewport(element) {
  if (!element) return false;
  const rect = element.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}
