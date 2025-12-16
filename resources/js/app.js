import "./bootstrap";
import sortable from "./sortable";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";
import "flatpickr/dist/plugins/monthSelect/style.css";

// Expose monthSelectPlugin globally for MaryUI datepicker
window.monthSelectPlugin = monthSelectPlugin;

import "./flipbook"; // initialize flipbook viewer globally
import "./modulePage"; // module page scripts (video gate, yt api, accordion)

// ApexCharts for dashboard charts
import ApexCharts from "apexcharts";
window.ApexCharts = ApexCharts;

// Plyr player (default UI, no customizations)
import Plyr from "plyr";
import "plyr/dist/plyr.css";

function initPlyr() {
    try {
        const els = document.querySelectorAll(".js-plyr");
        if (!els || !els.length) return;
        const players = Plyr.setup(els, {
            hideControls: true,
            seekTime: 10, // rewind/fast-forward step (we only show rewind)
            // Hide timeline bar, but keep time labels and add a rewind button
            controls: [
                "play-large",
                "play",
                "rewind",
                "current-time",
                "duration",
                "mute",
                "volume",
                "fullscreen",
            ],
            youtube: {
                rel: 0,
                modestbranding: 1,
                iv_load_policy: 3,
                playsinline: 1,
                // Keep native YT controls minimal; Plyr provides UI
                controls: 0,
                // Use privacy-enhanced domain if you want: noCookie: true
                // noCookie: true,
            },
        });
        // Wire ended -> module-video-ended for gating
        const hostEls = Array.from(els);
        players.forEach((p, idx) => {
            try {
                const host = p.elements?.original || hostEls[idx] || null;
                const endId = host?.getAttribute?.("data-end-id") || null;
                p.on("ended", () => {
                    if (endId)
                        window.dispatchEvent(
                            new CustomEvent("module-video-ended", {
                                detail: { id: endId },
                            })
                        );
                });
                // Remove resolution menu: keep Plyr's default gear (speed); do not attach custom quality menu
                // try { if (p?.provider === 'youtube') { p.on('ready', () => attachYoutubeQualityMenu(p, host)); } } catch(_){ }
                // Add shield to suppress YT overlays (title, share, watch later, endscreen)
                try {
                    if (p?.provider === "youtube") {
                        p.on("ready", () => attachYoutubeShield(p));
                    }
                } catch (_) {}

                // Ensure rewind + time labels sit directly beside play/pause
                try {
                    p.on("ready", () => repositionControls(p));
                } catch (_) {}

                // --- Anti-forward skip, allow rewind ---
                let maxWatched = 0;
                const SLACK = 0.5; // tolerance seconds
                const seekInput = p?.elements?.inputs?.seek || null;
                const progress =
                    p?.elements?.progress?.container ||
                    p?.elements?.progress ||
                    null;
                const container = p?.elements?.container || null;
                // Make timeline view-only (non-interactive) but visible
                try {
                    if (seekInput) {
                        seekInput.style.pointerEvents = "none";
                        seekInput.setAttribute("aria-disabled", "true");
                        seekInput.tabIndex = -1;
                    }
                    if (progress) {
                        progress.style.pointerEvents = "none";
                    }
                } catch (_) {}
                // Track max watched
                p.on("timeupdate", () => {
                    try {
                        const t = p.currentTime || 0;
                        if (t > maxWatched) maxWatched = t;
                    } catch (_) {}
                });
                function clampForward() {
                    try {
                        const t = p.currentTime || 0;
                        if (t > maxWatched + SLACK) {
                            p.currentTime = Math.max(0, maxWatched);
                        }
                    } catch (_) {}
                }
                p.on("seeking", clampForward);
                p.on("seeked", clampForward);
                // Lock playback rate to 1x in case external code tries to change it
                try {
                    p.on("ratechange", () => {
                        try {
                            if (Math.abs((p.speed || 1) - 1) > 1e-3)
                                p.speed = 1;
                        } catch (_) {}
                    });
                } catch (_) {}
                // Allow rewind via keyboard, block forward via keyboard
                try {
                    if (container) {
                        container.addEventListener(
                            "keydown",
                            (e) => {
                                const key = e.key || e.code;
                                if (key === "ArrowRight" || key === "Right") {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return false;
                                }
                                if (key === "ArrowLeft" || key === "Left") {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    try {
                                        p.currentTime = Math.max(
                                            0,
                                            (p.currentTime || 0) - 5
                                        );
                                    } catch (_) {}
                                    return false;
                                }
                            },
                            true
                        );
                    }
                } catch (_) {}
            } catch (e) {}
        });
    } catch (e) {}
}

// Add a transparent overlay above YouTube iframe to block native overlays while preserving Plyr controls
function attachYoutubeShield(player) {
    try {
        const container = player?.elements?.container || null;
        const wrapper = container?.querySelector?.(".plyr__video-wrapper");
        if (!wrapper) return;
        // Avoid duplicates
        if (wrapper.querySelector("[data-yt-shield]")) return;
        wrapper.style.position = wrapper.style.position || "relative";
        const shield = document.createElement("div");
        shield.setAttribute("data-yt-shield", "");
        Object.assign(shield.style, {
            position: "absolute",
            inset: "0",
            zIndex: "2",
            background: "transparent",
        });
        // Toggle play/pause on click so UX remains natural while blocking YT overlay clicks
        shield.addEventListener("click", (e) => {
            e.stopPropagation();
            try {
                if (player.playing) player.pause();
                else player.play();
            } catch (_) {}
        });
        // Block context menu
        shield.addEventListener("contextmenu", (e) => {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        wrapper.appendChild(shield);
    } catch (e) {}
}

// Move current-time, duration, and rewind right next to the play/pause button
function repositionControls(player) {
    try {
        const bar = player?.elements?.controls;
        if (!bar) return;
        const sel = (q) => bar.querySelector(q);
        const playBtn = sel('[data-plyr="play"]');
        const rewindBtn = sel('[data-plyr="rewind"]');
        const curTime = sel('[data-plyr="current-time"]');
        const dur = sel('[data-plyr="duration"]');
        if (!playBtn) return;
        const insertAfter = (ref, node) => {
            if (!ref || !node || ref === node) return;
            ref.parentNode.insertBefore(node, ref.nextSibling);
        };
        // Order: play, rewind, current-time, duration
        if (rewindBtn) insertAfter(playBtn, rewindBtn);
        if (curTime) insertAfter(rewindBtn || playBtn, curTime);
        if (dur) insertAfter(curTime || rewindBtn || playBtn, dur);
    } catch (e) {}
}

// Build a simple Quality (resolution) menu and wire it to Plyr's gear button for YouTube
function attachYoutubeQualityMenu(player, hostEl) {
    try {
        const yt = player?.embed;
        if (!yt || typeof yt.getAvailableQualityLevels !== "function") return;
        const aspect =
            (hostEl && hostEl.closest && hostEl.closest(".aspect-video")) ||
            null;
        const container =
            aspect ||
            player?.elements?.container ||
            hostEl?.parentElement ||
            document.body;

        // UI elements: wrapper and menu panel
        const wrap = document.createElement("div");
        wrap.style.position = "absolute";
        wrap.style.right = "12px";
        wrap.style.bottom = "60px";
        wrap.style.zIndex = "50";
        const menu = document.createElement("div");
        menu.style.minWidth = "140px";
        menu.style.borderRadius = "8px";
        menu.style.background = "rgba(0,0,0,0.65)";
        menu.style.border = "1px solid rgba(255,255,255,0.12)";
        menu.style.boxShadow = "0 10px 20px rgba(0,0,0,0.25)";
        menu.style.padding = "6px";
        menu.style.display = "none";
        wrap.appendChild(menu);

        function mapLabel(q) {
            switch (q) {
                case "highres":
                    return "2160p+";
                case "hd2160":
                    return "2160p";
                case "hd1440":
                    return "1440p";
                case "hd1080":
                    return "1080p";
                case "hd720":
                    return "720p";
                case "large":
                    return "480p";
                case "medium":
                    return "360p";
                case "small":
                    return "240p";
                case "tiny":
                    return "144p";
                case "default":
                    return "Auto";
                default:
                    return q || "Auto";
            }
        }
        function getQualitiesSafe() {
            try {
                return yt.getAvailableQualityLevels() || [];
            } catch (_) {
                return [];
            }
        }
        function getCurQ() {
            try {
                return yt.getPlaybackQuality?.() || "default";
            } catch (_) {
                return "default";
            }
        }
        function resolveAvailable(desired) {
            try {
                if (!desired || desired === "default") return "default";
                const order = [
                    "highres",
                    "hd2160",
                    "hd1440",
                    "hd1080",
                    "hd720",
                    "large",
                    "medium",
                    "small",
                    "tiny",
                ];
                const avail = Object.create(null);
                getQualitiesSafe().forEach((q) => {
                    if (q) avail[q] = true;
                });
                const idx = order.indexOf(desired);
                for (let i = idx < 0 ? 0 : idx; i < order.length; i++) {
                    const q = order[i];
                    if (avail[q]) return q;
                }
                return "default";
            } catch (_) {
                return desired;
            }
        }
        function setQ(q) {
            try {
                const target = resolveAvailable(q);
                if (target === "default") {
                    yt.setPlaybackQuality("default");
                } else {
                    if (typeof yt.setPlaybackQualityRange === "function") {
                        try {
                            yt.setPlaybackQualityRange(target, target);
                        } catch (_) {}
                    }
                    yt.setPlaybackQuality(target);
                    [200, 500, 1000].forEach((ms) =>
                        setTimeout(() => {
                            try {
                                if (yt.getPlaybackQuality?.() !== target)
                                    yt.setPlaybackQuality(target);
                            } catch (_) {}
                        }, ms)
                    );
                }
            } catch (_) {}
        }
        function rebuildMenu() {
            const levels = getQualitiesSafe();
            const normalized = [];
            const seen = Object.create(null);
            levels.forEach((q) => {
                if (!q) return;
                const k = q.toLowerCase() === "auto" ? "default" : q;
                if (!seen[k]) {
                    seen[k] = true;
                    normalized.push(k);
                }
            });
            const items = ["default"].concat(
                normalized.filter((q) => q !== "default")
            );
            const cur = (() => {
                const c = getCurQ();
                return c === "auto" ? "default" : c;
            })();
            menu.innerHTML = "";
            items.forEach((q) => {
                const opt = document.createElement("button");
                opt.type = "button";
                opt.textContent = mapLabel(q);
                opt.style.display = "block";
                opt.style.width = "100%";
                opt.style.textAlign = "left";
                opt.style.fontSize = "12px";
                opt.style.padding = "6px 8px";
                opt.style.borderRadius = "6px";
                opt.style.color = q === cur ? "#fff" : "#e5e7eb";
                opt.style.background =
                    q === cur ? "rgba(255,255,255,0.15)" : "transparent";
                opt.addEventListener("click", (e) => {
                    e.stopPropagation();
                    setQ(q);
                    hideMenu();
                });
                menu.appendChild(opt);
            });
        }
        function toggleMenu() {
            if (menu.style.display === "none") {
                rebuildMenu();
                menu.style.display = "block";
            } else {
                menu.style.display = "none";
            }
        }
        function hideMenu() {
            menu.style.display = "none";
        }

        document.addEventListener("click", hideMenu);
        try {
            yt.addEventListener("onPlaybackQualityChange", () => {
                /* optional refresh */
            });
        } catch (_) {}

        const mount = () => {
            try {
                container.appendChild(wrap);
            } catch (_) {}
        };
        const readyPoll = setInterval(() => {
            try {
                yt.getPlayerState();
                clearInterval(readyPoll);
                mount();
            } catch (_) {}
        }, 150);

        // Rewire gear/settings button to open our Quality menu
        try {
            const controlsBar = player?.elements?.controls;
            const gearBtn =
                controlsBar?.querySelector?.('[data-plyr="settings"]') || null;
            const defaultMenu =
                player?.elements?.container?.querySelector?.(".plyr__menu") ||
                null;
            if (defaultMenu) defaultMenu.style.display = "none";
            if (gearBtn)
                gearBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenu();
                });
        } catch (_) {}
    } catch (e) {}
}

document.addEventListener("DOMContentLoaded", initPlyr);
document.addEventListener("livewire:navigated", initPlyr);

Alpine.directive("sortable", (el, { expression }, { evaluateLater }) => {
    let getCallback = evaluateLater(expression);

    sortable(el, (order) => {
        getCallback((callback) => {
            if (typeof callback === "function") {
                callback(order);
            }
        });
    });
});
