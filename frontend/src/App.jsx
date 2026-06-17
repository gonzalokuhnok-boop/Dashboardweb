import React, { useState } from 'react';
import AuthOverlay from './components/AuthOverlay';
import Dashboard from './components/Dashboard';

function App() {
  const [user, setUser] = useState(null);

  const handleLoginSuccess = (userData) => {
    setUser(userData);
  };

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    setUser(null);
  };

  return (
    <div className="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col relative">
      {!user && <AuthOverlay onLoginSuccess={handleLoginSuccess} />}
      {user && <Dashboard user={user} onLogout={handleLogout} />}
    </div>
  );
}

export default App;
