import axios from 'axios'
import Cookies from 'js-cookie'

// Get base URL from environment or use default
const getBaseURL = () => {
  const isDev = import.meta.env.DEV;
  const isProd = import.meta.env.PROD;
  const viteApiUrl = import.meta.env.VITE_API_URL;
  
  // PRODUCTION: Always use absolute URLs
  if (isProd) {
    // Use VITE_API_URL if configured
    if (viteApiUrl && viteApiUrl !== 'undefined') {
      return `${viteApiUrl}/api`;
    }
    
    // For Render deployments, use the correct backend URL
    if (typeof window !== 'undefined' && window.location.hostname.includes('onrender.com')) {
      return 'https://lms-backend-qn4t.onrender.com/api';
    }
    
    throw new Error('Production deployment requires absolute backend URL. Please configure VITE_API_URL.');
  }
  
  // DEVELOPMENT: Use Vite proxy
  return '/api';
};


const api = axios.create({
  baseURL: getBaseURL(),
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = Cookies.get('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
)

// Response interceptor to handle token refresh
api.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
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
        Cookies.remove('token');
        window.location.href = '/auth/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error)
  }
)

export { api }
