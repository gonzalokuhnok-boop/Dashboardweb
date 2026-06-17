import React, { useState } from 'react';
import axios from 'axios';

// ⚠️ URL de Producción en InfinityFree
const API_URL = 'https://tradingdashboard.infinityfree.me/api.php'; 

export default function AuthOverlay({ onLoginSuccess }) {
  const [isRegisterMode, setIsRegisterMode] = useState(false);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    password: ''
  });

  const toggleMode = (e) => {
    e.preventDefault();
    setIsRegisterMode(!isRegisterMode);
    setError(null);
  };

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const action = isRegisterMode ? 'register' : 'login';
      const response = await axios.post(`${API_URL}?action=${action}`, formData);
      
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token);
        localStorage.setItem('user_data', JSON.stringify(response.data.user || { email: formData.email }));
        onLoginSuccess(response.data.user);
      }
    } catch (err) {
      console.warn("Fallo en la API:", err.message);
      // BYPASS LOCAL: Para que puedas ver el dashboard de React aunque no tengas el servidor PHP corriendo en localhost.
      if (err.message === 'Network Error' || err.code === 'ERR_NETWORK') {
        alert("⚠️ ATENCIÓN: No se pudo conectar a " + API_URL + ". \n\nComo estamos en la vista previa y no tienes un servidor PHP local, activaré el 'Modo Demostración' para que puedas ver cómo queda el Dashboard por dentro.");
        onLoginSuccess({ email: formData.email, name: formData.firstName || 'Demo' });
      } else if (err.response && err.response.data && err.response.data.error) {
        setError(err.response.data.error);
      } else {
        setError('Error de conexión con el servidor.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-slate-950/90 z-[200] flex items-center justify-center p-4 backdrop-blur-md">
      <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-8 shadow-2xl relative overflow-hidden">
        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-emerald-400"></div>
        
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-500/20">
            <i className="fa-solid fa-chart-line text-3xl text-blue-500"></i>
          </div>
          <h2 className="text-2xl font-bold text-white">Trading Journal Pro</h2>
          <p className="text-slate-400 text-sm mt-1">Conecta a tu nube de Trading</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {isRegisterMode && (
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-1">Nombre</label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><i className="fa-solid fa-user"></i></span>
                  <input type="text" name="firstName" value={formData.firstName} onChange={handleChange} required className="w-full bg-slate-950 border border-slate-800 rounded-lg pl-10 pr-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors" />
                </div>
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-1">Apellido</label>
                <div className="relative">
                  <input type="text" name="lastName" value={formData.lastName} onChange={handleChange} required className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors" />
                </div>
              </div>
            </div>
          )}

          <div>
            <label className="block text-xs font-bold text-slate-400 uppercase mb-1">Correo Electrónico</label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><i className="fa-solid fa-envelope"></i></span>
              <input type="email" name="email" value={formData.email} onChange={handleChange} required placeholder="trader@institucion.com" className="w-full bg-slate-950 border border-slate-800 rounded-lg pl-10 pr-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors" />
            </div>
          </div>
          <div>
            <label className="block text-xs font-bold text-slate-400 uppercase mb-1">Contraseña</label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><i className="fa-solid fa-lock"></i></span>
              <input type="password" name="password" value={formData.password} onChange={handleChange} required placeholder="••••••••" className="w-full bg-slate-950 border border-slate-800 rounded-lg pl-10 pr-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors" />
            </div>
          </div>
          
          {error && (
            <div className="text-rose-400 text-xs font-medium bg-rose-500/10 p-3 rounded border border-rose-500/20 text-center leading-relaxed">
              {error}
            </div>
          )}
          
          <button type="submit" disabled={loading} className="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-blue-900/20 transition-all flex justify-center items-center disabled:opacity-50">
            {loading ? <i className="fa-solid fa-spinner fa-spin"></i> : <span>{isRegisterMode ? 'Crear Cuenta' : 'Iniciar Sesión'}</span>}
          </button>
        </form>

        <div className="mt-6 text-center">
          <p className="text-sm text-slate-400">
            <span>{isRegisterMode ? '¿Ya tienes cuenta?' : '¿No tienes cuenta?'}</span> 
            <button onClick={toggleMode} type="button" className="text-blue-400 hover:text-blue-300 font-bold ml-1 transition-colors">
              {isRegisterMode ? 'Inicia Sesión' : 'Regístrate'}
            </button>
          </p>
        </div>
      </div>
    </div>
  );
}
