import { createContext, useContext, useReducer, useEffect } from 'react'
import Cookies from 'js-cookie'
import axios from 'axios'
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
      // Use the API endpoint which will be redirected to PHP
      const response = await api.post('/login', { email, password });
      
      // Check if response has the expected structure
      if (!response.data || typeof response.data !== 'object') {
        throw new Error('Invalid response format received from server');
      }
      
      const { user, token, success } = response.data;
      
      if (!success || !user || !token) {
        throw new Error(response.data.message || 'Login failed');
      }
      
      Cookies.set('token', token, { expires: 7 });
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      dispatch({
        type: 'LOGIN_SUCCESS',
        payload: { user, token }
      });
      
    } catch (error) {
      console.error('Login failed:', error.message);
      if (error.response?.data?.message) {
        throw new Error(error.response.data.message);
      }
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
    try {
      // Test 1: Health check
      const healthResponse = await api.get('/test');
      
      // Test 2: Debug endpoint
      const debugResponse = await api.get('/debug');
      
      // Test 3: Debug login
      const debugLoginResponse = await api.post('/debug/login', {
        email: 'admin@example.com',
        password: 'password'
      });
      
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
    try {
      const response = await api.post('/debug/login', { email, password });
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
