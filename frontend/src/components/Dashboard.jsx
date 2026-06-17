import React from 'react';

export default function Dashboard({ user, onLogout }) {
  return (
    <div className="flex-1 flex flex-col p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
      <header className="flex flex-col xl:flex-row items-start xl:items-center justify-between mb-6 space-y-4 xl:space-y-0">
        <div>
          <h1 className="text-2xl font-bold text-white flex items-center">
            <i className="fa-solid fa-chart-line mr-3 text-blue-500"></i>
            Trading Journal Pro
          </h1>
          <div className="flex flex-wrap gap-4 text-xs font-mono text-slate-400 mt-2">
            <div title="Hora Local"><i className="fa-regular fa-clock text-slate-500"></i> <span className="text-slate-300">En vivo</span></div>
          </div>
        </div>
        
        <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 w-full xl:w-auto">
          <div className="group relative z-50 flex-1 sm:flex-none">
            <button className="w-full sm:w-auto justify-center bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg transition-colors flex items-center border border-slate-700/50">
              <i className="fa-solid fa-user-astronaut mr-2 text-blue-400"></i>
              <span className="text-xs font-medium max-w-[100px] truncate">{user?.email || 'Usuario'}</span>
            </button>
            <div className="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all overflow-hidden">
              <button onClick={onLogout} className="w-full text-left px-4 py-3 text-sm text-rose-400 hover:bg-slate-800 hover:text-rose-300 flex items-center">
                <i className="fa-solid fa-right-from-bracket mr-2 w-4 text-center"></i> Cerrar Sesión
              </button>
            </div>
          </div>

          <button className="flex-1 sm:flex-none justify-center bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium flex items-center shadow-lg shadow-blue-900/20 transition-all">
            <i className="fa-solid fa-plus mr-2"></i> Registrar Trade
          </button>
        </div>
      </header>

      <main className="space-y-6">
        {/* KPI ROW */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          {['Balance Actual', 'Consistencia (50%)', 'Colchón Libre', 'PnL Neto', 'Profit Factor'].map((kpi, idx) => (
            <div key={idx} className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
              <div>
                <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">{kpi}</p>
                <h3 className="text-2xl font-bold text-white money-blur">
                  {idx === 0 || idx === 2 || idx === 3 ? '$0.00' : '0.00'}
                </h3>
              </div>
            </div>
          ))}
        </div>

        {/* CHARTS ROW */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm lg:col-span-2 flex flex-col relative h-[300px] justify-center items-center">
            <h3 className="text-lg font-semibold text-white mb-4 absolute top-5 left-5">Curva de Equidad</h3>
            <p className="text-slate-500">Gráfico de Chart.js Migrando...</p>
          </div>
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm flex flex-col h-[300px] justify-center items-center">
            <h3 className="text-lg font-semibold text-white mb-4 absolute top-5 left-5">Calendario</h3>
            <p className="text-slate-500">Componente Calendario...</p>
          </div>
        </div>
      </main>
    </div>
  );
}
