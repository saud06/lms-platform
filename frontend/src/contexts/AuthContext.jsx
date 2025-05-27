import { createContext, useContext, useReducer, useEffect } from 'react'
import Cookies from 'js-cookie'
import { api } from '../lib/api'

const AuthContext = createContext()

const initialState = {
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: true
}

function authReducer(state, action) {
  switch (action.type) {
    case 'SET_LOADING':
      return { ...state, isLoading: action.payload }
    case 'LOGIN_SUCCESS':
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        isAuthenticated: true,
        isLoading: false
      }
    case 'LOGOUT':
      return {
        ...state,
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false
      }
    case 'SET_USER':
      return { ...state, user: action.payload }
    default:
      return state
  }
}

export function AuthProvider({ children }) {
  const [state, dispatch] = useReducer(authReducer, initialState)

  useEffect(() => {
    const token = Cookies.get('token')
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`
      fetchUser()
    } else {
      dispatch({ type: 'SET_LOADING', payload: false })
    }
  }, [])

  const fetchUser = async () => {
    try {
      const response = await api.get('/me')
      dispatch({
        type: 'LOGIN_SUCCESS',
        payload: {
          user: response.data.user,
          token: Cookies.get('token')
        }
      })
    } catch (error) {
      console.error('Failed to fetch user:', error)
      logout()
    }
  }

  const login = async (email, password) => {
    try {
      console.log('=== AuthContext Login Attempt v1.0.3 ===', {
        email,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        currentUrl: window.location.href
      });
      
      console.log('AuthContext - API Configuration Check:', {
        apiDefaults: api.defaults,
        baseURL: api.defaults?.baseURL,
        timeout: api.defaults?.timeout,
        headers: api.defaults?.headers
      });
      
      const response = await api.post('/login', { email, password });
      
      console.log('=== AuthContext Login Response ===', {
        status: response.status,
        statusText: response.statusText,
        headers: response.headers,
        dataType: typeof response.data,
        dataKeys: Object.keys(response.data || {}),
        data: response.data,
        responseText: typeof response.data === 'string' ? response.data.substring(0, 200) : 'N/A',
        timestamp: new Date().toISOString()
      });
      
      // Check if response has the expected structure
      if (!response.data || typeof response.data !== 'object') {
        console.error('=== Invalid Response Details ===', {
          responseType: typeof response.data,
          responseContent: response.data,
          contentLength: response.data ? String(response.data).length : 0,
          isHtml: typeof response.data === 'string' && response.data.includes('<html>'),
          is404: typeof response.data === 'string' && response.data.includes('404'),
          isErrorPage: typeof response.data === 'string' && (response.data.includes('Error') || response.data.includes('Exception')),
          requestUrl: response.config?.url,
          fullRequestUrl: response.config?.baseURL + response.config?.url,
          statusCode: response.status,
          responseHeaders: response.headers,
          actualResponse: String(response.data).substring(0, 500)
        });
        
        // Try to determine the specific issue
        let errorMessage = 'Invalid response format received from server';
        if (typeof response.data === 'string') {
          if (response.data.includes('404')) {
            errorMessage = 'Login endpoint not found (404). Check if backend is deployed correctly.';
          } else if (response.data.includes('<html>')) {
            errorMessage = 'Received HTML instead of JSON. This indicates a server error or incorrect URL.';
          } else if (response.data.includes('CORS')) {
            errorMessage = 'CORS error detected. Backend may not allow requests from this domain.';
          }
        }
        
        throw new Error(`${errorMessage} Expected JSON object with user and token fields, but got ${typeof response.data}. Response: ${String(response.data).substring(0, 200)}`);
      }
      
      const { user, token } = response.data;
      
      if (!user || !token) {
        console.error('Login response missing required fields:', {
          hasUser: !!user,
          hasToken: !!token,
          responseData: response.data
        });
        throw new Error('Login response missing user or token');
      }
      
      Cookies.set('token', token, { expires: 7 });
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      dispatch({
        type: 'LOGIN_SUCCESS',
        payload: { user, token }
      });
      
      console.log('=== AuthContext Login Success ===', {
        user: user,
        tokenLength: token?.length,
        timestamp: new Date().toISOString()
      });
      
    } catch (error) {
      console.error('=== AuthContext Login Error ===', {
        message: error.message,
        stack: error.stack,
        status: error.response?.status,
        statusText: error.response?.statusText,
        responseData: error.response?.data,
        responseText: typeof error.response?.data === 'string' ? error.response.data.substring(0, 200) : 'N/A',
        requestUrl: error.config?.url,
        requestMethod: error.config?.method,
        requestData: error.config?.data,
        fullRequestUrl: error.config ? (error.config.baseURL + error.config.url) : 'unknown',
        isNetworkError: !error.response,
        timestamp: new Date().toISOString()
      });
      throw error;
    }
  }

  const register = async (data) => {
    try {
      const response = await api.post('/register', data)
      const { user, token } = response.data
      
      Cookies.set('token', token, { expires: 7 })
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      dispatch({
        type: 'LOGIN_SUCCESS',
        payload: { user, token }
      })
    } catch (error) {
      throw error
    }
  }

  const logout = () => {
    Cookies.remove('token')
    delete api.defaults.headers.common['Authorization']
    dispatch({ type: 'LOGOUT' })
  }

  const setUser = (user) => {
    dispatch({ type: 'SET_USER', payload: user })
  }

  const refreshToken = async () => {
    try {
      const response = await api.post('/refresh')
      const { token } = response.data
      
      Cookies.set('token', token, { expires: 7 })
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      return token
    } catch (error) {
      logout()
      throw error
    }
  }

  const testApiConnectivity = async () => {
    console.log('=== Testing API Connectivity ===');
    
    try {
      // Test 1: Health check
      console.log('Test 1: Health check');
      const healthResponse = await api.get('/test');
      console.log('Health check success:', healthResponse.data);
      
      // Test 2: Debug endpoint
      console.log('Test 2: Debug endpoint');
      const debugResponse = await api.get('/debug');
      console.log('Debug endpoint success:', debugResponse.data);
      
      // Test 3: Debug login
      console.log('Test 3: Debug login');
      const debugLoginResponse = await api.post('/debug/login', {
        email: 'admin@example.com',
        password: 'password'
      });
      console.log('Debug login success:', debugLoginResponse.data);
      
      return {
        success: true,
        health: healthResponse.data,
        debug: debugResponse.data,
        debugLogin: debugLoginResponse.data
      };
      
    } catch (error) {
      console.error('API connectivity test failed:', error);
      return {
        success: false,
        error: error.message,
        details: {
          status: error.response?.status,
          statusText: error.response?.statusText,
          data: error.response?.data,
          config: error.config
        }
      };
    }
  };

  const debugLogin = async (email = 'admin@example.com', password = 'password') => {
    console.log('=== Debug Login Test ===', { email });
    
    try {
      const response = await api.post('/debug/login', { email, password });
      console.log('Debug login response:', response.data);
      return response.data;
    } catch (error) {
      console.error('Debug login failed:', error);
      throw error;
    }
  };

  const value = {
    ...state,
    login,
    register,
    logout,
    setUser,
    refreshToken,
    testApiConnectivity,
    debugLogin
  }

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
