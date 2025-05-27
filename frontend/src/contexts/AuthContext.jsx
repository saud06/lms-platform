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
      console.log('=== AuthContext Login Attempt v1.0.2 ===', {
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
        timestamp: new Date().toISOString()
      });
      
      // Check if response has the expected structure
      if (!response.data || typeof response.data !== 'object') {
        throw new Error('Invalid response format: expected object, got ' + typeof response.data);
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
        requestUrl: error.config?.url,
        requestMethod: error.config?.method,
        requestData: error.config?.data,
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

  const value = {
    ...state,
    login,
    register,
    logout,
    setUser,
    refreshToken
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
