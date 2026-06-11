/**
 * Edunova API Client
 * Handles all API requests with error handling and retries
 */

class APIClient {
  constructor(baseURL = Config.API.BASE_URL) {
    this.baseURL = baseURL;
    this.timeout = Config.API.TIMEOUT;
    this.retryAttempts = Config.API.RETRY_ATTEMPTS;
  }

  /**
   * Make API request with error handling and retry logic
   * @param {string} endpoint - API endpoint
   * @param {object} options - Fetch options
   * @returns {Promise<object>} Response data
   */
  async request(endpoint, options = {}) {
    const url = this.buildURL(endpoint);
    const config = this.buildConfig(options);

    for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
      try {
        const response = await this.fetchWithTimeout(url, config);
        
        if (!response.ok) {
          throw new Error(`HTTP Error: ${response.status}`);
        }

        return await response.json();
      } catch (error) {
        // Last attempt failed, throw error
        if (attempt === this.retryAttempts) {
          console.error(`API request failed after ${this.retryAttempts} attempts:`, error);
          throw new Error(`Failed to complete request: ${error.message}`);
        }
        
        // Wait before retrying (exponential backoff)
        await this.delay(1000 * Math.pow(2, attempt - 1));
      }
    }
  }

  /**
   * GET request
   * @param {string} endpoint - API endpoint
   * @returns {Promise<object>} Response data
   */
  async get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  /**
   * POST request
   * @param {string} endpoint - API endpoint
   * @param {object} data - Request body
   * @returns {Promise<object>} Response data
   */
  async post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  /**
   * PUT request
   * @param {string} endpoint - API endpoint
   * @param {object} data - Request body
   * @returns {Promise<object>} Response data
   */
  async put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  /**
   * DELETE request
   * @param {string} endpoint - API endpoint
   * @returns {Promise<object>} Response data
   */
  async delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }

  /**
   * Build full URL with base URL and endpoint
   * @private
   * @param {string} endpoint - API endpoint
   * @returns {string} Full URL
   */
  buildURL(endpoint) {
    if (endpoint.startsWith('http')) {
      return endpoint;
    }
    return `${this.baseURL}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;
  }

  /**
   * Build fetch configuration with headers and auth
   * @private
   * @param {object} options - Request options
   * @returns {object} Fetch configuration
   */
  buildConfig(options = {}) {
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      },
      ...options
    };

    // Add authorization token if available
    const token = getAuthToken();
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }

    return config;
  }

  /**
   * Fetch with timeout
   * @private
   * @param {string} url - Request URL
   * @param {object} config - Fetch configuration
   * @returns {Promise<Response>} Fetch response
   */
  async fetchWithTimeout(url, config) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);

    try {
      return await fetch(url, { ...config, signal: controller.signal });
    } finally {
      clearTimeout(timeoutId);
    }
  }

  /**
   * Delay utility for retries
   * @private
   * @param {number} ms - Milliseconds to delay
   * @returns {Promise<void>}
   */
  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Create global API client instance
const api = new APIClient();
