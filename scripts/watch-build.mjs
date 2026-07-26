import { spawn } from 'node:child_process'
import { existsSync, rmSync } from 'node:fs'
import chokidar from 'chokidar'

/**
 * Compila a public/build (páginas rápidas vía Nginx) y vuelve a compilar
 * cuando cambian Blade/CSS/JS. Necesario porque `vite build --watch` no
 * observa @source de Tailwind (vistas Blade) de forma fiable en Docker.
 */
const roots = [
    'resources/views',
    'resources/css',
    'resources/js',
    'vite.config.js',
]

function runBuild() {
    return new Promise((resolve, reject) => {
        console.log('[assets] Compilando Vite + Tailwind…')
        const child = spawn('npm', ['run', 'build'], {
            stdio: 'inherit',
            env: process.env,
        })
        child.on('exit', (code) => {
            if (code === 0) {
                console.log('[assets] Listo → public/build (recarga el navegador si no ves el cambio)')
                resolve()
            } else {
                reject(new Error(`vite build terminó con código ${code}`))
            }
        })
    })
}

if (existsSync('public/hot')) {
    rmSync('public/hot')
    console.log('[assets] Eliminado public/hot residual (Laravel usará public/build)')
}

let building = false
let pending = false
let timer = null

async function scheduleBuild() {
    if (building) {
        pending = true
        return
    }

    building = true
    try {
        await runBuild()
    } catch (error) {
        console.error('[assets]', error.message || error)
    } finally {
        building = false
        if (pending) {
            pending = false
            void scheduleBuild()
        }
    }
}

await scheduleBuild()

const usePolling = process.env.CHOKIDAR_USEPOLLING === 'true'
const interval = Number(process.env.CHOKIDAR_INTERVAL || 500)

console.log(`[assets] Observando vistas/CSS/JS (polling=${usePolling}, interval=${interval}ms)…`)

chokidar
    .watch(roots, {
        ignoreInitial: true,
        usePolling,
        interval,
        awaitWriteFinish: { stabilityThreshold: 250, pollInterval: 100 },
    })
    .on('all', (event, path) => {
        console.log(`[assets] ${event}: ${path}`)
        clearTimeout(timer)
        timer = setTimeout(() => {
            void scheduleBuild()
        }, 400)
    })
