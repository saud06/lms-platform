import axios from 'axios'
import Cookies from 'js-cookie'

// Mock API for testing when backend is unavailable
const MOCK_MODE = false;

const mockUsers = [
  { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin' },
  { id: 2, name: 'Instructor User', email: 'instructor@example.com', role: 'instructor' },
  { id: 3, name: 'Student User', email: 'student@example.com', role: 'student' }
];

const mockAPI = {
  post: (url, data) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        if (url === '/login') {
          const user = mockUsers.find(u => u.email === data.email);
          if (user && data.password === 'password') {
            resolve({
              data: {
                message: 'Login successful',
                user: user,
                token: 'mock_token_' + Date.now()
              }
            });
          } else {
            throw new Error('Invalid credentials');
          }
        } else if (url === '/register') {
          const newUser = {
            id: mockUsers.length + 1,
            name: data.name,
            email: data.email,
            role: 'student'
          };
          resolve({
            data: {
              message: 'User registered successfully',
              user: newUser,
              token: 'mock_token_' + Date.now()
            }
          });
        } else if (url === '/refresh') {
          resolve({
            data: { token: 'refreshed_mock_token_' + Date.now() }
          });
        }
      }, 500); // Simulate network delay
    });
  },
  get: (url) => {
    return new Promise((resolve) => {
      setTimeout(() => {
        if (url === '/me') {
          resolve({
            data: {
              user: mockUsers[0] // Return admin user for demo
            }
          });
        }
      }, 300);
    });
  }
};

// Get base URL from environment or use default
const getBaseURL = () => {
  const isDev = import.meta.env.DEV;
  const isProd = import.meta.env.PROD;
  const viteApiUrl = import.meta.env.VITE_API_URL;
  
  console.log('API URL Detection:', {
    isDev,
    isProd,
    viteApiUrl,
    mode: import.meta.env.MODE,
    timestamp: new Date().toISOString()
  });
  
  // In development, use the Vite proxy setup
  if (isDev) {
    return '/api';
  }
  
  // In production, determine the backend URL
  if (isProd) {
    // If VITE_API_URL is set, use it (for Render deployment)
    if (viteApiUrl) {
      const backendUrl = `${viteApiUrl}/api`;
      console.log('Production: Using VITE_API_URL ->', backendUrl);
      return backendUrl;
    }
    
    // Fallback: try to infer backend URL from current domain
    const currentUrl = window.location.origin;
    const backendUrl = currentUrl.replace('lms-frontend', 'lms-backend');
    console.log('Production: Inferred backend URL ->', backendUrl + '/api');
    return `${backendUrl}/api`;
  }
  
  // Fallback
  return '/api';
};

const api = MOCK_MODE ? mockAPI : axios.create({
  baseURL: getBaseURL(),
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
})

// Log the API configuration for debugging
const apiConfig = {
  mockMode: MOCK_MODE,
  baseURL: getBaseURL(),
  environment: import.meta.env.MODE,
  viteApiUrl: import.meta.env.VITE_API_URL,
  isProduction: import.meta.env.PROD,
  isDevelopment: import.meta.env.DEV,
  currentOrigin: typeof window !== 'undefined' ? window.location.origin : 'SSR',
  userAgent: typeof navigator !== 'undefined' ? navigator.userAgent : 'SSR',
  buildTimestamp: '2025-08-27T19:30:00Z',
  version: '1.0.2'
};

console.log('=== API Configuration v1.0.2 ===', apiConfig);

// Additional network debugging
if (typeof window !== 'undefined') {
  console.log('Network Environment:', {
    online: navigator.onLine,
    url: window.location.href,
    protocol: window.location.protocol,
    host: window.location.host
  });
}

// Only add interceptors for real axios instance
if (!MOCK_MODE) {
  // Request interceptor to add auth token
  api.interceptors.request.use(
    (config) => {
      const token = Cookies.get('token');
      const fullUrl = config.baseURL + config.url;
      
      console.log('=== API Request Details ===', {
        method: config.method?.toUpperCase(),
        url: config.url,
        fullUrl: fullUrl,
        baseURL: config.baseURL,
        data: config.data,
        headers: config.headers,
        hasToken: !!token,
        timestamp: new Date().toISOString()
      });
      
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      
      return config;
    },
    (error) => {
      console.error('=== API Request Setup Error ===', {
        message: error.message,
        config: error.config,
        timestamp: new Date().toISOString()
      });
      return Promise.reject(error);
    }
  )
}

// Only add response interceptor for real axios instance
if (!MOCK_MODE) {
  // Response interceptor to handle token refresh
  api.interceptors.response.use(
    (response) => {
      console.log('=== API Response Success ===', {
        method: response.config.method?.toUpperCase(),
        url: response.config.url,
        fullUrl: response.config.baseURL + response.config.url,
        status: response.status,
        statusText: response.statusText,
        dataType: typeof response.data,
        dataLength: JSON.stringify(response.data).length,
        headers: response.headers,
        timestamp: new Date().toISOString()
      });
      return response;
    },
    async (error) => {
      console.error('=== API Response Error ===', {
        url: error.config?.url,
        fullUrl: error.config ? (error.config.baseURL + error.config.url) : 'unknown',
        method: error.config?.method,
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        message: error.message,
        code: error.code,
        isNetworkError: !error.response,
        timestamp: new Date().toISOString()
      });
      
      const originalRequest = error.config

      if (error.response?.status === 401 && !originalRequest._retry) {
        originalRequest._retry = true

        try {
          const response = await api.post('/refresh')
          const { token } = response.data
          
          Cookies.set('token', token, { expires: 7 })
          originalRequest.headers.Authorization = `Bearer ${token}`
          
          return api(originalRequest)
        } catch (refreshError) {
          console.error('=== Token Refresh Failed ===', {
            error: refreshError.message,
            status: refreshError.response?.status,
            data: refreshError.response?.data,
            timestamp: new Date().toISOString()
          });
          Cookies.remove('token');
          window.location.href = '/auth/login';
          return Promise.reject(refreshError);
        }
      }

      return Promise.reject(error)
    }
  )
}

export { api }
