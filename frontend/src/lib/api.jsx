import axios from 'axios'
import Cookies from 'js-cookie'

// Mock API for testing when backend is unavailable
const MOCK_MODE = false;

// Debug environment variables immediately
console.log('=== Environment Debug at Module Load ===', {
  'import.meta.env': import.meta.env,
  'import.meta.env.DEV': import.meta.env.DEV,
  'import.meta.env.PROD': import.meta.env.PROD,
  'import.meta.env.MODE': import.meta.env.MODE,
  'import.meta.env.VITE_API_URL': import.meta.env.VITE_API_URL,
  'window.location.href': typeof window !== 'undefined' ? window.location.href : 'SSR',
  'window.location.hostname': typeof window !== 'undefined' ? window.location.hostname : 'SSR',
  'window.location.origin': typeof window !== 'undefined' ? window.location.origin : 'SSR',
  timestamp: new Date().toISOString()
});

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
  const mode = import.meta.env.MODE;
  
  // Get current location info
  const hasWindow = typeof window !== 'undefined';
  const currentHostname = hasWindow ? window.location.hostname : '';
  const currentOrigin = hasWindow ? window.location.origin : '';
  const currentHref = hasWindow ? window.location.href : '';
  
  console.log('=== API URL Detection v1.0.6 ===', {
    isDev,
    isProd,
    viteApiUrl,
    mode,
    hasWindow,
    currentHostname,
    currentOrigin,
    currentHref,
    timestamp: new Date().toISOString()
  });
  
  // BULLETPROOF FIX: Force backend URL for ANY onrender.com deployment
  if (hasWindow && currentHostname.includes('onrender.com')) {
    console.log('=== RENDER DEPLOYMENT DETECTED - FORCING BACKEND URL ===');
    
    let backendUrl;
    
    // Strategy 1: Use VITE_API_URL if available
    if (viteApiUrl && viteApiUrl !== 'undefined' && !viteApiUrl.includes('localhost')) {
      backendUrl = `${viteApiUrl}/api`;
      console.log('Strategy 1 - Using VITE_API_URL:', backendUrl);
    }
    // Strategy 2: Extract service pattern from current URL
    else if (currentHostname.includes('lms-frontend')) {
      // Extract the suffix (e.g., "-2ksd" from "lms-frontend-2ksd")
      const parts = currentHostname.split('.');
      const servicePart = parts[0]; // "lms-frontend-XXXX"
      const suffix = servicePart.replace('lms-frontend', ''); // "-XXXX"
      
      // For known actual backend, use the real URL
      if (servicePart.includes('2ksd')) {
        backendUrl = 'https://lms-backend-thlg.onrender.com/api';
        console.log('Strategy 2A - Known backend mapping:', backendUrl);
      } else {
        backendUrl = `https://lms-backend${suffix}.onrender.com/api`;
        console.log('Strategy 2B - Service pattern extraction:', backendUrl);
      }
    }
    // Strategy 3: Try direct replacement
    else {
      backendUrl = currentOrigin.replace('lms-frontend', 'lms-backend') + '/api';
      console.log('Strategy 3 - Direct replacement:', backendUrl);
    }
    
    console.log('=== RENDER: Final backend URL ===', backendUrl);
    return backendUrl;
  }
  
  // DEVELOPMENT: Use Vite proxy
  if (isDev) {
    console.log('DEVELOPMENT: Using development proxy: /api');
    return '/api';
  }
  
  // PRODUCTION: Other platforms
  if (isProd) {
    if (viteApiUrl && viteApiUrl !== 'undefined') {
      const backendUrl = `${viteApiUrl}/api`;
      console.log('PRODUCTION: Using VITE_API_URL ->', backendUrl);
      return backendUrl;
    }
  }
  
  // Final fallback
  console.log('FALLBACK: Using fallback: /api');
  return '/api';
};

// Get the base URL immediately for debugging
const detectedBaseURL = getBaseURL();
console.log('=== Final API Base URL ===', detectedBaseURL);

// Quick validation of the detected URL
if (typeof window !== 'undefined') {
  console.log('=== Backend URL Validation v1.0.6 ===', {
    detectedURL: detectedBaseURL,
    isHttps: detectedBaseURL.startsWith('https://'),
    hasOnRender: detectedBaseURL.includes('onrender.com'),
    hasApiPath: detectedBaseURL.includes('/api'),
    isRelativeUrl: detectedBaseURL.startsWith('/'),
    isAbsoluteUrl: detectedBaseURL.startsWith('http'),
    currentDomain: window.location.hostname,
    currentOrigin: window.location.origin,
    servicePattern: window.location.hostname.split('.')[0], // e.g., "lms-frontend-2ksd"
    expectedBackendDomain: window.location.hostname.replace('lms-frontend', 'lms-backend'),
    hostnameChecks: {
      'lms-frontend': window.location.hostname.includes('lms-frontend'),
      'onrender.com': window.location.hostname.includes('onrender.com'),
      'full_hostname': window.location.hostname
    }
  });
  
  // Alert if still using relative URL on production
  if (window.location.hostname.includes('onrender.com') && detectedBaseURL.startsWith('/')) {
    console.error('🚨 CRITICAL ERROR: Still using relative URL on Render deployment!');
    console.error('This will cause API requests to fail. URL detection logic needs fixing.');
    console.error('Expected absolute URL like: https://lms-backend-XXXX.onrender.com/api');
  }
}

const api = MOCK_MODE ? mockAPI : axios.create({
  baseURL: detectedBaseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
})

// Log the API configuration for debugging
const apiConfig = {
  mockMode: MOCK_MODE,
  detectedBaseURL: detectedBaseURL,
  actualApiBaseURL: api.defaults?.baseURL,
  environment: import.meta.env.MODE,
  viteApiUrl: import.meta.env.VITE_API_URL,
  isProduction: import.meta.env.PROD,
  isDevelopment: import.meta.env.DEV,
  currentOrigin: typeof window !== 'undefined' ? window.location.origin : 'SSR',
  userAgent: typeof navigator !== 'undefined' ? navigator.userAgent.substring(0, 50) : 'SSR',
  buildTimestamp: '2025-08-27T20:20:00Z',
  version: '1.0.7'
};

console.log('=== API Configuration v1.0.7 ===', apiConfig);

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
