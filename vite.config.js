// import { defineConfig } from "vite";
// import os from "os";
// import laravel from "laravel-vite-plugin";
// import tailwindcss from "@tailwindcss/vite";

// function detectLANIPv4() {
//     const nets = os.networkInterfaces();
//     for (const name of Object.keys(nets)) {
//         for (const net of nets[name] || []) {
//             if (
//                 net.family === "IPv4" &&
//                 !net.internal &&
//                 net.address !== "127.0.0.1"
//             ) {
//                 return net.address;
//             }
//         }
//     }
//     return "127.0.0.1";
// }

// const explicitHost = process.env.VITE_HMR_HOST;
// const detectedIP = detectLANIPv4();
// const hmrHost = explicitHost || detectedIP;
// const hmrPort = Number(process.env.VITE_HMR_PORT || 5173);

// export default defineConfig(() => {
//     return {
//         plugins: [
//             laravel({
//                 input: ["resources/css/app.css", "resources/js/app.js"],
//                 refresh: true,
//             }),
//             tailwindcss(),
//         ],
//         server: {
//             host: "0.0.0.0",
//             port: hmrPort,
//             origin: `http://${hmrHost}:${hmrPort}`,
//             hmr: {
//                 host: hmrHost,
//                 port: hmrPort,
//                 protocol: "ws",
//             },
//             cors: {
//                 origin: ["http://localhost:8000", "http://26.160.75.15:8000"],
//             },
//         },
//         define: {
//             __VITE_HMR_ORIGIN__: JSON.stringify(`http://${hmrHost}:${hmrPort}`),
//         },
//     };
// });

// PRODUCTION CONFIGURATION
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
