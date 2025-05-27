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
  // In production, use the environment variable from Render
  if (import.meta.env.PROD && import.meta.env.VITE_API_URL) {
    return `${import.meta.env.VITE_API_URL}/api`;
  }
  // In development, use the proxy setup
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
console.log('API Configuration:', {
  mockMode: MOCK_MODE,
  baseURL: getBaseURL(),
  environment: import.meta.env.MODE,
  viteApiUrl: import.meta.env.VITE_API_URL
});

// Only add interceptors for real axios instance
if (!MOCK_MODE) {
  // Request interceptor to add auth token
  api.interceptors.request.use(
    (config) => {
      console.log('API Request:', config.method?.toUpperCase(), config.url, config.data)
      const token = Cookies.get('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      console.error('API Request Error:', error)
      return Promise.reject(error)
    }
  )
}

// Only add response interceptor for real axios instance
if (!MOCK_MODE) {
  // Response interceptor to handle token refresh
  api.interceptors.response.use(
    (response) => {
      console.log('API Response:', response.config.method?.toUpperCase(), response.config.url, response.status);
      return response;
    },
    async (error) => {
      console.error('API Error:', {
        url: error.config?.url,
        method: error.config?.method,
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        message: error.message
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
          console.error('Token refresh failed:', refreshError);
          Cookies.remove('token')
          window.location.href = '/auth/login'
          return Promise.reject(refreshError)
        }
      }

      return Promise.reject(error)
    }
  )
}

export { api }
