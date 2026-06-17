import React from 'react';

export default function Dashboard({ user, onLogout }) {
  return (
    <div className="flex-col min-h-screen flex bg-slate-950 text-slate-200 font-sans">
      
      {/* TICKER DE PRECIOS */}
      <div className="w-full bg-slate-900 border-b border-slate-800">
          <div className="tradingview-widget-container">
              <div className="tradingview-widget-container__widget"></div>
              {/* Nota: En React, los scripts externos a veces requieren un useEffect o un componente especial, pero dejamos el contenedor visual por ahora */}
              <div className="p-2 text-center text-xs text-slate-500 font-mono">
                 [Ticker de TradingView Cargando...]
              </div>
          </div>
      </div>

      <div className="p-4 sm:p-6 lg:p-8 flex-1">
          {/* HEADER Y RELOJES */}
          <header className="max-w-7xl mx-auto flex flex-col xl:flex-row items-start xl:items-center justify-between mb-6 space-y-4 xl:space-y-0">
              <div>
                  <h1 className="text-2xl font-bold text-white flex items-center">
                      <i className="fa-solid fa-chart-line mr-3 text-blue-500"></i>
                      Trading Journal Pro
                  </h1>
                  <div className="flex flex-wrap gap-4 text-xs font-mono text-slate-400 mt-2">
                      <div title="Hora Local"><i className="fa-regular fa-clock text-slate-500"></i> <span className="text-slate-300">12:00:00</span></div>
                      <div title="New York"><i className="fa-regular fa-clock text-blue-500"></i> NY: <span className="text-blue-300">11:00:00</span></div>
                      <div title="London"><i className="fa-regular fa-clock text-emerald-500"></i> LDN: <span className="text-emerald-300">16:00:00</span></div>
                  </div>
                  <div className="mt-2">
                     <span className="flex w-max items-center text-emerald-400 bg-emerald-400/10 px-3 py-1.5 rounded-lg border border-emerald-400/20 text-xs font-bold shadow-[0_0_10px_rgba(16,185,129,0.15)]"><i className="fa-solid fa-crosshairs fa-fade mr-2"></i> KILLZONE: ESPERANDO SWEEP</span>
                  </div>
              </div>
              
              <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 w-full xl:w-auto">
                  <button className="hidden bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white px-4 py-2 rounded-lg font-bold shadow-lg shadow-amber-900/20 text-sm md:flex items-center transition-all border border-amber-400/50">
                      <i className="fa-solid fa-crown mr-2"></i> Panel Admin
                  </button>

                  <div className="flex items-center space-x-2 bg-slate-900 px-3 py-2 rounded-lg border border-slate-700 shadow-inner w-full sm:w-auto">
                      <i className="fa-solid fa-layer-group text-blue-400"></i>
                      <select className="bg-transparent text-white text-sm font-medium focus:outline-none cursor-pointer flex-1 sm:w-auto min-w-[140px]">
                          <option value="global" className="bg-slate-900">🌐 Vista Global</option>
                      </select>
                      <button className="text-slate-500 hover:text-blue-400 ml-2 transition-colors" title="Gestionar Cuentas"><i className="fa-solid fa-gear"></i></button>
                  </div>

                  <div className="flex flex-wrap gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                      <div className="group relative z-50 flex-1 sm:flex-none">
                          <button className="w-full sm:w-auto justify-center bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg transition-colors flex items-center border border-slate-700/50">
                              <i className="fa-solid fa-user-astronaut mr-2 text-blue-400"></i>
                              <span className="text-xs font-medium max-w-[100px] truncate">{user?.email || 'Usuario'}</span>
                          </button>
                          <div className="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all overflow-hidden">
                              <button className="w-full text-left px-4 py-3 text-sm text-slate-300 hover:bg-slate-800 hover:text-white flex items-center border-b border-slate-800">
                                  <i className="fa-solid fa-id-card mr-2 w-4 text-center"></i> Mi Perfil
                              </button>
                              <button onClick={onLogout} className="w-full text-left px-4 py-3 text-sm text-rose-400 hover:bg-slate-800 hover:text-rose-300 flex items-center">
                                  <i className="fa-solid fa-right-from-bracket mr-2 w-4 text-center"></i> Cerrar Sesión
                              </button>
                          </div>
                      </div>

                      <div className="flex gap-2">
                          <button className="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/50 px-3 py-2.5 rounded-lg transition-colors flex items-center text-sm" title="Restaurar JSON Antiguo">
                              <i className="fa-solid fa-upload text-blue-400"></i><span className="hidden md:inline ml-2">JSON</span>
                          </button>
                          
                          <button className="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/50 px-3 py-2.5 rounded-lg transition-colors flex items-center text-sm" title="Guardar copia Nube">
                              <i className="fa-solid fa-download text-emerald-400"></i><span className="hidden lg:inline ml-2">Exportar</span>
                          </button>
                          
                          <button className="bg-indigo-900/50 hover:bg-indigo-800/50 text-indigo-300 border border-indigo-700/50 px-3 py-2.5 rounded-lg transition-colors flex items-center text-sm font-medium" title="Sincronizar Cuentas Fondeo">
                              <i className="fa-solid fa-plug text-indigo-400"></i><span className="hidden sm:inline ml-2">Conectar API</span>
                          </button>
                      </div>

                      <button className="bg-blue-900/40 text-blue-300 border border-blue-500/30 hover:bg-blue-800/50 px-4 py-2.5 rounded-lg transition-colors flex items-center font-bold shadow-lg shadow-blue-900/20" title="Abrir Terminal de Broker / Gráficos">
                          <i className="fa-solid fa-chart-candlestick mr-2 text-blue-400"></i> Operar / Terminal
                      </button>
                      
                      <button className="flex-1 sm:flex-none justify-center bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium flex items-center shadow-lg shadow-blue-900/20 transition-all">
                          <i className="fa-solid fa-plus mr-2"></i> Registrar Trade
                      </button>
                  </div>
              </div>
          </header>

          <main className="max-w-7xl mx-auto space-y-6">
              
              {/* BARRA DE OBJETIVO Y STATUS RISK GUARD */}
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm relative overflow-hidden">
                  <div className="hidden absolute top-0 left-0 w-full h-1 bg-rose-600"></div>
                  <div className="flex justify-between items-end mb-3">
                      <div>
                          <div className="flex items-center space-x-2">
                              <h3 className="text-sm font-medium text-slate-400">Objetivo de Ganancia <span className="ml-2 px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 text-[10px] font-bold border border-blue-500/30 uppercase">VISTA GLOBAL</span></h3>
                          </div>
                          <div className="flex items-baseline space-x-3 mt-1 group cursor-pointer" title="Editar Objetivo">
                              <span className="text-2xl font-bold text-white money-blur">$3,000.00</span>
                              <i className="fa-solid fa-pencil text-slate-600 group-hover:text-blue-400 text-sm hidden"></i>
                          </div>
                      </div>
                      <div className="text-right">
                          <p className="text-sm font-medium text-emerald-400 money-blur">Faltan: $3,000.00</p>
                          <p className="text-xs text-slate-500">0% Completado</p>
                      </div>
                  </div>
                  <div className="w-full bg-slate-800 rounded-full h-3 overflow-hidden shadow-inner">
                      <div className="bg-gradient-to-r from-blue-600 to-emerald-400 h-3 rounded-full transition-all duration-1000 relative" style={{ width: '0%' }}>
                          <div className="absolute inset-0 bg-white/20" style={{ backgroundImage: 'linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent)' }}></div>
                      </div>
                  </div>
              </div>

              {/* ROW DE HERRAMIENTAS INSTITUCIONALES */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  {/* Calculadora de Lotes */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm flex flex-col justify-between">
                      <div className="flex justify-between items-center mb-4">
                          <h3 className="text-lg font-semibold text-white"><i className="fa-solid fa-calculator text-blue-400 mr-2"></i>Calculadora de Lotes</h3>
                          <span className="text-[10px] text-slate-500 font-mono">Basado en Balance Actual</span>
                      </div>
                      <div className="grid grid-cols-3 gap-3 mb-4">
                          <div>
                              <label className="text-[10px] text-slate-400 uppercase font-bold">Riesgo (%)</label>
                              <input type="number" defaultValue="0.5" step="0.1" className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white text-sm mt-1 focus:border-blue-500 focus:outline-none" />
                          </div>
                          <div>
                              <label className="text-[10px] text-slate-400 uppercase font-bold">Stop Loss (Pips)</label>
                              <input type="number" defaultValue="10" className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white text-sm mt-1 focus:border-blue-500 focus:outline-none" />
                          </div>
                          <div>
                              <label className="text-[10px] text-slate-400 uppercase font-bold">USD/Pip (Stnd)</label>
                              <input type="number" defaultValue="10" className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white text-sm mt-1 focus:border-blue-500 focus:outline-none" />
                          </div>
                      </div>
                      <div className="bg-slate-950 border border-slate-800 rounded-lg p-3 flex justify-between items-center">
                          <div>
                              <p className="text-[10px] text-slate-400 uppercase font-bold">Riesgo en USD</p>
                              <p className="text-lg font-bold text-rose-400 money-blur">$250.00</p>
                          </div>
                          <div className="text-right">
                              <p className="text-[10px] text-slate-400 uppercase font-bold">Lotes a Operar</p>
                              <p className="text-2xl font-bold text-emerald-400">2.50</p>
                          </div>
                      </div>
                  </div>

                  {/* Proyección Cuantitativa */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm flex flex-col justify-between relative overflow-hidden">
                      <div className="absolute -right-4 -bottom-4 opacity-5"><i className="fa-solid fa-brain text-9xl text-white"></i></div>
                      <div className="flex justify-between items-center mb-4 relative z-10">
                          <h3 className="text-lg font-semibold text-white"><i className="fa-solid fa-chart-network text-purple-400 mr-2"></i>Simulador Quant (Monte Carlo)</h3>
                          <button className="bg-purple-600/20 text-purple-400 border border-purple-500/30 hover:bg-purple-600/40 text-[10px] font-bold px-3 py-1 rounded transition-colors">
                              <i className="fa-solid fa-play mr-1"></i> TEST DE ESTRÉS
                          </button>
                      </div>
                      <div className="flex justify-between items-end relative z-10 mb-2">
                          <p className="text-xs text-slate-400">Probabilidad Matemática (Meta):</p>
                          <span className="text-2xl font-bold text-white">--%</span>
                      </div>
                      <div className="grid grid-cols-2 gap-4 relative z-10">
                          <div className="bg-slate-950 border border-slate-800 rounded-lg p-3">
                              <p className="text-[10px] text-slate-400 uppercase font-bold">Valor Esperado (EV/Trade)</p>
                              <p className="text-xl font-bold text-white money-blur">$0.00</p>
                          </div>
                          <div className="bg-slate-950 border border-emerald-900/50 rounded-lg p-3 shadow-[inset_0_0_15px_rgba(16,185,129,0.05)]">
                              <p className="text-[10px] text-emerald-500 uppercase font-bold">Proyección (100 trades)</p>
                              <p className="text-xl font-bold text-emerald-400 money-blur">+$0.00</p>
                          </div>
                      </div>
                  </div>
              </div>

              {/* KPIs PROP FIRM */}
              <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                  {/* Balance */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Balance Actual</p>
                          <h3 className="text-2xl font-bold text-white money-blur">$50,000.00</h3>
                      </div>
                      <div className="text-xs text-slate-500 mt-2 flex justify-between cursor-pointer group">
                          <span className="money-blur">Cap. Inicial: $50k</span> 
                      </div>
                  </div>

                  {/* Consistencia */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 relative overflow-hidden flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Consistencia (50%)</p>
                          <h3 className="text-2xl font-bold text-white">0.0%</h3>
                          <p className="text-[10px] text-slate-500 mt-1 money-blur">Mejor día: $0.00</p>
                      </div>
                  </div>

                  {/* Drawdown */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <div className="flex justify-between items-center mb-1 cursor-pointer group">
                              <p className="text-slate-400 text-[11px] font-bold uppercase">Colchón Libre</p>
                          </div>
                          <h3 className="text-2xl font-bold text-emerald-500 money-blur">$2,000.00</h3>
                      </div>
                      <p className="text-[10px] text-slate-500 mt-2 font-medium">Quiebre: <span className="text-rose-400 font-bold money-blur">$48,000</span></p>
                  </div>

                  {/* PnL Neto */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">PnL Neto</p>
                          <h3 className="text-2xl font-bold text-emerald-400 money-blur">+$0.00</h3>
                      </div>
                      <p className="text-xs text-slate-500 mt-2">0 trades totales</p>
                  </div>

                  {/* Profit Factor */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Profit Factor</p>
                          <h3 className="text-2xl font-bold text-white">0.00</h3>
                      </div>
                      <p className="text-[10px] text-slate-500 mt-2">Bruto Win / Bruto Loss</p>
                  </div>

                  {/* Win Rate */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <div className="flex justify-between items-start mb-1">
                              <p className="text-slate-400 text-[11px] font-bold uppercase">Win Rate</p>
                              <span className="text-[10px] text-slate-500 font-mono bg-slate-800 px-1.5 py-0.5 rounded">1:0</span>
                          </div>
                          <h3 className="text-2xl font-bold text-white">0.0%</h3>
                      </div>
                      <p className="text-xs text-slate-500 mt-2">0 completados</p>
                  </div>

                  {/* Factor Recup */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Factor Recup.</p>
                          <h3 className="text-2xl font-bold text-white">0.00</h3>
                      </div>
                      <p className="text-[10px] text-slate-500 mt-2">Net. PnL / Max DD</p>
                  </div>

                  {/* Racha Actual */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex flex-col justify-between">
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Racha Actual</p>
                          <h3 className="text-2xl font-bold text-white">-</h3>
                      </div>
                      <p className="text-[10px] text-slate-500 mt-2">Max. racha L: 0</p>
                  </div>

                  {/* Estado Cuenta */}
                  <div className="col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm hover:border-slate-700 flex justify-between items-center relative overflow-hidden">
                      <div className="absolute top-0 right-0 h-full w-2 bg-blue-500"></div>
                      <div>
                          <p className="text-slate-400 text-[11px] font-bold uppercase mb-1">Estado Cuenta</p>
                          <h3 className="text-xl font-bold text-blue-400">Activa</h3>
                          <p className="text-xs text-slate-500 mt-1">Sin evaluación</p>
                      </div>
                  </div>
              </div>

              {/* GRÁFICOS Y CALENDARIO */}
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                  {/* Gráfico principal */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm lg:col-span-2 flex flex-col relative h-[350px]">
                      <h3 className="text-lg font-semibold text-white mb-4">Curva de Equidad</h3>
                      <div className="flex-1 w-full bg-slate-950/50 rounded-lg border border-slate-800/50 flex items-center justify-center">
                          <p className="text-slate-500">Gráfico de Chart.js migrando...</p>
                      </div>
                  </div>

                  {/* Calendario */}
                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm h-[350px] flex flex-col">
                      <div className="flex justify-between items-center mb-4">
                          <button className="text-slate-400 hover:text-white"><i className="fa-solid fa-chevron-left"></i></button>
                          <h3 className="text-sm font-bold text-white uppercase tracking-wider">Junio 2026</h3>
                          <button className="text-slate-400 hover:text-white"><i className="fa-solid fa-chevron-right"></i></button>
                      </div>
                      <div className="flex-1 w-full bg-slate-950/50 rounded-lg border border-slate-800/50 flex items-center justify-center">
                          <p className="text-slate-500 text-sm">Calendario migrando...</p>
                      </div>
                  </div>
              </div>
          </main>
      </div>
    </div>
  );
}
