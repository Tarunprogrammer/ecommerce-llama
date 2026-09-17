<?php
session_start();

// Get order info from session if available
$order_info = $_SESSION['last_order'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Lumina</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #6d28d9;
            --accent: #ec4899;
            --cyber-blue: #06b6d4;
            --cyber-green: #10b981;
            --dark: #030712;
            --glass-bg: rgba(8, 10, 24, 0.75);
            --glass-border: rgba(255, 255, 255, 0.08);
            
            /* Image0.png Pig Exact Color Schemes */
            --pig-skin: #FFD6E8; 
            --pig-skin-shaded: #FF9EBB;
            --pig-snout: #FF5C8D;
            --pig-snout-dark: #D64A75;
            --pig-shadow: rgba(244, 92, 141, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark);
            background-image: 
                linear-gradient(rgba(3, 7, 18, 0.4) 1px, transparent 1px),
                linear-gradient(90deg, rgba(3, 7, 18, 0.4) 1px, transparent 1px),
                radial-gradient(circle at 0% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            background-size: 40px 40px, 40px 40px, auto, auto;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            padding: 3rem 1rem;
            perspective: 2000px;
            position: relative;
        }

        /* Ambient flowing nebulas */
        .nebula {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.3;
            pointer-events: none;
            mix-blend-mode: screen;
            z-index: 0;
            animation: moveNebula 25s ease-in-out infinite alternate;
        }
        .nebula-purple { background: radial-gradient(circle, var(--primary) 0%, transparent 70%); top: -10%; left: -10%; }
        .nebula-pink { background: radial-gradient(circle, var(--accent) 0%, transparent 70%); bottom: -10%; right: -10%; animation-delay: -8s; }

        @keyframes moveNebula {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            100% { transform: translate(150px, 100px) scale(1.3) rotate(360deg); }
        }

        /* 60 FPS Particle Canvas */
        #confetti-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        /* Completely Overhauled Main Interface Deck */
        .interface-grid {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 2rem;
            z-index: 10;
            transform-style: preserve-3d;
            animation: formEntry 1s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes formEntry {
            from { transform: translateY(80px) rotateX(15deg); opacity: 0; }
            to { transform: translateY(0) rotateX(0deg); opacity: 1; }
        }

        /* Glassmorphic Cyber-Deck Containers */
        .cyber-deck {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 2px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            height: fit-content;
        }

        /* Tech telemetry markings */
        .tech-corners::before, .tech-corners::after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            border-color: var(--primary);
            border-style: solid;
            pointer-events: none;
        }
        .tech-corners::before { top: 16px; left: 16px; border-width: 2px 0 0 2px; }
        .tech-corners::after { bottom: 16px; right: 16px; border-width: 0 2px 2px 0; }

        .system-header {
            font-family: 'Share Tech Mono', monospace;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--cyber-blue);
            letter-spacing: 2px;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.75rem;
            text-transform: uppercase;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--cyber-green);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--cyber-green);
            animation: statusPulse 1.5s infinite alternate;
        }

        @keyframes statusPulse {
            0% { opacity: 0.4; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1.1); }
        }

        /* Centered Teleportation Chamber Home Stage */
        .chamber-stage {
            width: 240px;
            height: 240px;
            margin: 1.5rem auto 2.5rem;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            cursor: pointer;
        }

        /* 3D Teleportation rings */
        .quantum-ring {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%) rotateX(75deg);
            width: 170px;
            height: 170px;
            border: 3px double var(--accent);
            border-radius: 50%;
            box-shadow: 
                0 0 20px var(--accent),
                inset 0 0 15px var(--accent);
            z-index: 1;
            animation: rotateChamber 12s linear infinite;
        }

        @keyframes rotateChamber {
            from { transform: translateX(-50%) rotateX(75deg) rotate(0deg); }
            to { transform: translateX(-50%) rotateX(75deg) rotate(360deg); }
        }

        /* Energy stream wall */
        .energy-beam {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            height: 170px;
            background: linear-gradient(0deg, rgba(236, 72, 153, 0.2) 0%, rgba(139, 92, 246, 0.05) 50%, rgba(255, 255, 255, 0) 100%);
            border-left: 1px dashed rgba(236, 72, 153, 0.4);
            border-right: 1px dashed rgba(236, 72, 153, 0.4);
            z-index: 2;
            pointer-events: none;
            border-radius: 70px / 15px;
            animation: beamPulse 3s ease-in-out infinite alternate;
        }

        @keyframes beamPulse {
            0% { opacity: 0.3; transform: translateX(-50%) scaleX(0.95); }
            100% { opacity: 0.8; transform: translateX(-50%) scaleX(1.05); }
        }

        /* Come From Home Wrapper */
        .pig-entrance-wrapper {
            width: 140px;
            height: 160px;
            z-index: 10;
            transform-origin: bottom center;
            transform: scale(0);
            opacity: 0;
            animation: landingBounce 1.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.8s forwards;
        }

        @keyframes landingBounce {
            0% { transform: scale(0) translateY(-100px) rotate(-30deg); opacity: 0; }
            65% { transform: scale(1.15) translateY(-15px) rotate(10deg); opacity: 1; }
            100% { transform: scale(1) translateY(0) rotate(0deg); opacity: 1; }
        }

        .pig-character {
            width: 100%;
            height: 100%;
            transform-origin: bottom center;
            animation: idleBreath 2s ease-in-out infinite alternate;
        }

        @keyframes idleBreath {
            0% { transform: scaleY(1); }
            100% { transform: scaleY(0.95) scaleX(1.03); }
        }

        /* Reactive wave ripples emitted on squeal */
        .squeal-wave {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translate(-50%, 50%) scale(0.1);
            width: 200px;
            height: 200px;
            border: 4px solid var(--accent);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
            z-index: 12;
        }

        @keyframes rippleExplode {
            0% { transform: translate(-50%, 50%) scale(0.2); opacity: 1; filter: blur(0px); }
            100% { transform: translate(-50%, 50%) scale(2.2); opacity: 0; filter: blur(5px); }
        }

        /* Sound-reactive CSS equalizer layout */
        .equalizer-hud {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 4px;
            height: 30px;
            margin-top: -10px;
            margin-bottom: 1.5rem;
        }

        .eq-bar {
            width: 4px;
            height: 4px;
            background: var(--cyber-blue);
            border-radius: 2px;
            transition: height 0.1s ease;
        }

        /* Dynamic Class Utilities */
        .snout-wiggle {
            animation: snoutShake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }
        .oink-jump {
            animation: oinkBounce 0.6s cubic-bezier(0.25, 1, 0.5, 1) both;
        }

        @keyframes snoutShake {
            0%, 100% { transform: scale(1); }
            30% { transform: scale(1.3) translateY(-1.5px); }
            60% { transform: scale(1.1) translateY(1px); }
        }

        @keyframes oinkBounce {
            0%, 100% { transform: translateY(0) scale(1); }
            35% { transform: translateY(-30px) scaleY(1.15) scaleX(0.9); }
            65% { transform: translateY(5px) scaleY(0.9) scaleX(1.1); }
        }

        /* Control HUD & Dashboard Details */
        .control-deck-row {
            margin-bottom: 1.5rem;
        }

        .control-label {
            display: flex;
            justify-content: space-between;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .slider-control {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            transition: background 0.3s;
        }

        .slider-control::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--cyber-blue);
            cursor: pointer;
            box-shadow: 0 0 10px var(--cyber-blue);
        }

        .oink-button {
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, #db2777 100%);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 16px;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .oink-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.6);
            filter: brightness(1.1);
        }

        /* Order Info & Receipt Column */
        h1 {
            font-size: 2.6rem;
            font-weight: 900;
            letter-spacing: -1px;
            line-height: 1.1;
            background: linear-gradient(135deg, #fff 40%, var(--cyber-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .success-desc {
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .telemetry-receipt {
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.85rem;
            color: #64748b;
        }

        .receipt-row:last-child { margin-bottom: 0; }
        .receipt-row strong { color: #fff; }

        /* Quantum Step Tracker */
        .quantum-steps {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .step-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.5s;
        }

        .step-circle.active {
            border-color: var(--cyber-blue);
            background: rgba(6, 182, 212, 0.1);
            color: var(--cyber-blue);
            box-shadow: 0 0 8px rgba(6, 182, 212, 0.3);
        }

        .step-circle.completed {
            border-color: var(--cyber-green);
            background: rgba(16, 185, 129, 0.1);
            color: var(--cyber-green);
        }

        .step-label {
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }
        .step-row.active .step-label { color: #fff; }

        .btn-portal-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-decoration: none;
            padding: 1.2rem 2.5rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 
                0 10px 25px -5px rgba(139, 92, 246, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        .btn-portal-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px -5px rgba(139, 92, 246, 0.6);
            filter: brightness(1.1);
        }

        .snout-emoji-particle {
            position: absolute;
            pointer-events: none;
            z-index: 100;
            font-size: 2.2rem;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
        }

        @media (max-width: 820px) {
            .interface-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Space Nebulas -->
    <div class="nebula nebula-purple"></div>
    <div class="nebula nebula-pink"></div>

    <!-- Celebration Particles -->
    <canvas id="confetti-canvas"></canvas>

    <!-- Overhauled Interstellar Control Grid -->
    <div class="interface-grid" id="tilt-container">
        
        <!-- Left Deck: Pig Teleportation Chamber and Interactive Dashboard -->
        <div class="cyber-deck tech-corners">
            <div class="system-header">
                <span>[ CHAMBER: BETA-09 ]</span>
                <div class="system-status">
                    <div class="status-dot"></div>
                    <span>CONNECTED</span>
                </div>
            </div>

            <!-- Teleportation Chamber Home -->
            <div class="chamber-stage" onclick="triggerPigOinkSqueal(event)">
                <div class="quantum-ring"></div>
                <div class="energy-beam"></div>
                
                <!-- Shockwave Ripple -->
                <div class="squeal-wave" id="shockwave"></div>

                <!-- Come From Home Pig Character -->
                <div class="pig-entrance-wrapper" id="pig-character">
                    <svg class="pig-character" viewBox="0 0 140 160" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <!-- Skin Gradients from image0.png -->
                            <radialGradient id="bodySkin" cx="40%" cy="35%" r="60%">
                                <stop offset="0%" stop-color="#FFD6E8"/>
                                <stop offset="60%" stop-color="#FFD6E8"/> 
                                <stop offset="100%" stop-color="#FF9EBB"/>
                            </radialGradient>
                            <radialGradient id="headSkin" cx="42%" cy="38%" r="58%">
                                <stop offset="0%" stop-color="#FFD6E8"/>
                                <stop offset="65%" stop-color="#FFD6E8"/>
                                <stop offset="100%" stop-color="#FF9EBB"/>
                            </radialGradient>
                            <radialGradient id="snoutGrad" cx="35%" cy="30%" r="65%">
                                <stop offset="0%" stop-color="#FF5C8D"/>
                                <stop offset="100%" stop-color="#D64A75"/>
                            </radialGradient>
                            <linearGradient id="highlight" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="white" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="white" stop-opacity="0"/>
                            </linearGradient>
                        </defs>

                        <!-- Pig Tail -->
                        <g class="pig-tail">
                            <path d="M 36 110 Q 16 112 18 96 T 8 98" fill="none" stroke="#FF9EBB" stroke-width="6" stroke-linecap="round"/>
                            <path d="M 36 110 Q 16 112 18 96 T 8 98" fill="none" stroke="#FFD6E8" stroke-width="2" stroke-linecap="round"/>
                        </g>

                        <!-- Feet (Sitting Pose) -->
                        <g class="pig-leg-left">
                            <rect x="49" y="125" width="16" height="20" rx="8" fill="url(#bodySkin)"/>
                            <line x1="57" y1="136" x2="57" y2="145" stroke="#FF5C8D" stroke-width="1.5"/>
                        </g>

                        <g class="pig-leg-right">
                            <rect x="75" y="125" width="16" height="20" rx="8" fill="url(#bodySkin)"/>
                            <line x1="83" y1="136" x2="83" y2="145" stroke="#FF5C8D" stroke-width="1.5"/>
                        </g>

                        <!-- Arms -->
                        <g class="pig-arm-left">
                            <rect x="22" y="85" width="16" height="34" rx="8" transform="rotate(50 22 85)" fill="url(#bodySkin)"/>
                            <line x1="12" y1="102" x2="18" y2="108" stroke="#FF5C8D" stroke-width="1.5"/>
                        </g>

                        <g class="pig-arm-right">
                            <rect x="102" y="85" width="16" height="34" rx="8" transform="rotate(-50 102 85)" fill="url(#bodySkin)"/>
                            <line x1="128" y1="102" x2="122" y2="108" stroke="#FF5C8D" stroke-width="1.5"/>
                        </g>

                        <!-- Ears -->
                        <g class="pig-ear-left">
                            <path d="M 38 48 C 16 38 18 16 36 28 Z" fill="url(#bodySkin)"/>
                            <path d="M 34 44 C 22 37 24 24 32 32 Z" fill="#FF5C8D" opacity="0.6"/>
                        </g>

                        <g class="pig-ear-right">
                            <path d="M 102 48 C 124 38 122 16 104 28 Z" fill="url(#bodySkin)"/>
                            <path d="M 106 44 C 118 37 116 24 108 32 Z" fill="#FF5C8D" opacity="0.6"/>
                        </g>

                        <!-- Body Torso -->
                        <ellipse cx="70" cy="94" rx="43" ry="38" fill="url(#bodySkin)"/>
                        <ellipse cx="70" cy="100" rx="30" ry="24" fill="url(#highlight)" opacity="0.3"/>
                        <ellipse cx="70" cy="98" rx="26" ry="22" fill="#fff" opacity="0.2"/>

                        <!-- Head -->
                        <g class="pig-head">
                            <circle cx="70" cy="65" r="32" fill="url(#headSkin)"/>
                            <path d="M 44 48 Q 70 38 96 48" fill="none" stroke="url(#highlight)" stroke-width="3" stroke-linecap="round"/>
                            
                            <!-- Cheek Blush -->
                            <ellipse cx="46" cy="74" rx="8" ry="6" fill="#FF5C8D" opacity="0.4" filter="blur(2px)"/>
                            <ellipse cx="94" cy="74" rx="8" ry="6" fill="#FF5C8D" opacity="0.4" filter="blur(2px)"/>

                            <!-- Eyes -->
                            <circle cx="53" cy="62" r="4.5" fill="#0f172a"/>
                            <circle cx="51.5" cy="60" r="1.8" fill="#fff"/>
                            
                            <circle cx="87" cy="62" r="4.5" fill="#0f172a"/>
                            <circle cx="85.5" cy="60" r="1.8" fill="#fff"/>

                            <!-- Snout -->
                            <g id="pig-snout">
                                <rect x="56" y="70" width="28" height="17" rx="8.5" fill="url(#snoutGrad)"/>
                                <rect x="59" y="72" width="22" height="4" rx="2" fill="url(#highlight)" opacity="0.4"/>
                                <ellipse cx="64" cy="78.5" rx="2.5" ry="3.5" fill="#7a2034"/>
                                <ellipse cx="76" cy="78.5" rx="2.5" ry="3.5" fill="#7a2034"/>
                            </g>

                            <!-- Mouth -->
                            <path d="M 62 90 Q 70 96 78 90" fill="none" stroke="#7a2034" stroke-width="3" stroke-linecap="round"/>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Dynamic Sound Wave HUD -->
            <div class="equalizer-hud" id="hud-visualizer">
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
                <div class="eq-bar"></div>
            </div>

            <!-- HUD Deck Controllers -->
            <div class="control-deck-row">
                <div class="control-label">
                    <span>SQUEAL GAIN (VOLUME BOOST)</span>
                    <span id="vol-percentage">200%</span>
                </div>
                <input type="range" class="slider-control" id="gain-slider" min="0.5" max="4.0" step="0.1" value="2.0">
            </div>

            <div class="control-deck-row">
                <div class="control-label">
                    <span>CONFETTI FLOW VELOCITY</span>
                    <span id="confetti-label">STANDARD</span>
                </div>
                <input type="range" class="slider-control" id="confetti-slider" min="1.0" max="4.0" step="0.5" value="2.0">
            </div>

            <button class="oink-button" onclick="triggerPigOinkSqueal(event)">
                <i class="fa-solid fa-microphone-lines"></i> FORCE SQUEAL 🐽
            </button>
        </div>

        <!-- Right Deck: Interactive Confirmation Details -->
        <div class="cyber-deck tech-corners">
            <div class="system-header">
                <span>[ LOG: SYSTEM SUCCESS ]</span>
                <span>SECURE</span>
            </div>

            <h1>Order Confirmed</h1>
            <p class="success-desc">Quantum ledger synchronized! The hyper-shipment process is currently being assembled at Drone hangar 4.</p>

            <!-- Status Telemetry Receipt -->
            <div class="telemetry-receipt">
                <div class="receipt-row">
                    <span>TRANSACTION HASH</span>
                    <strong>#<?= htmlspecialchars($order_info['order_id'] ?? rand(1000, 9999)) ?></strong>
                </div>
                <div class="receipt-row">
                    <span>SETTLED CAPITAL</span>
                    <strong>₹<?= number_format($order_info['total'] ?? 0, 2) ?></strong>
                </div>
                <div class="receipt-row">
                    <span>SHIPMENT ROUTE</span>
                    <strong>ORBITAL AIR-DROP</strong>
                </div>
            </div>

            <!-- Cyber Step Timeline -->
            <div class="quantum-steps">
                <div class="step-row">
                    <div class="step-circle completed"><i class="fa-solid fa-check"></i></div>
                    <span class="step-label">Secure Payment Settled</span>
                </div>
                <div class="step-row active">
                    <div class="step-circle active"><i class="fa-solid fa-rotate"></i></div>
                    <span class="step-label">Quantum Inventory Logging</span>
                </div>
                <div class="step-row">
                    <div class="step-circle">3</div>
                    <span class="step-label">Pneumatic Hangar Dispatch</span>
                </div>
            </div>

            <a href="../index.php" class="btn-portal-home">
                RETURN TO PRODUCTS PORTAL <i class="fa-solid fa-rocket"></i>
            </a>
        </div>
    </div>

    <!-- Active JS Mechanics -->
    <script>
        // --- 1. PREMIUM 3D PARALLAX INTERFACE ---
        const tiltContainer = document.getElementById('tilt-container');

        body.addEventListener('mousemove', (e) => {
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            // Map movement coordinates to precise angle degrees
            const xAxis = (width / 2 - e.pageX) / 45;
            const yAxis = (height / 2 - e.pageY) / 45;
            
            tiltContainer.style.transform = `rotateY(${-xAxis}deg) rotateX(${yAxis}deg)`;
        });

        body.addEventListener('mouseleave', () => {
            tiltContainer.style.transform = 'rotateY(0deg) rotateX(0deg)';
        });


        // --- 2. MULTI-STAGE WEBAUDIO PIG FREQUENCY MODULATOR (VERY LOUD) ---
        let audioCtx;
        const gainSlider = document.getElementById('gain-slider');
        const volPercentage = document.getElementById('vol-percentage');

        // Display real-time gain percentage
        gainSlider.addEventListener('input', () => {
            const multiplier = Math.round(gainSlider.value * 100);
            volPercentage.innerText = `${multiplier}%`;
        });

        function synthesizeSqueal() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const now = audioCtx.currentTime;

            // Synthesis pipelines
            const modulator = audioCtx.createOscillator();
            const modGain = audioCtx.createGain();
            const carrier = audioCtx.createOscillator();
            const bandpassFilter = audioCtx.createBiquadFilter();
            const outputVolume = audioCtx.createGain();

            // Frequency Modulation configuration (creates authentic "squeal rumble")
            modulator.type = 'sawtooth';
            modulator.frequency.setValueAtTime(105, now);
            modulator.frequency.linearRampToValueAtTime(45, now + 0.45);

            modGain.gain.setValueAtTime(350, now);
            modGain.gain.linearRampToValueAtTime(90, now + 0.45);

            carrier.type = 'sawtooth';
            carrier.frequency.setValueAtTime(240, now);
            carrier.frequency.exponentialRampToValueAtTime(110, now + 0.45);

            bandpassFilter.type = 'bandpass';
            bandpassFilter.frequency.setValueAtTime(600, now);
            bandpassFilter.exponentialRampToValueAtTime(200, now + 0.45);
            bandpassFilter.Q.setValueAtTime(5.0, now);

            // Fetch amplified multiplier from the dashboard slider
            const gainFactor = parseFloat(gainSlider.value);
            outputVolume.gain.setValueAtTime(0.01, now);
            outputVolume.gain.linearRampToValueAtTime(gainFactor, now + 0.08); // VERY LOUD BOOST
            outputVolume.gain.exponentialRampToValueAtTime(0.01, now + 0.48);

            // FM Wire map
            modulator.connect(modGain);
            modGain.connect(carrier.frequency);
            carrier.connect(bandpassFilter);
            bandpassFilter.connect(outputVolume);
            outputVolume.connect(audioCtx.destination);

            // Execute Squeal
            modulator.start(now);
            carrier.start(now);
            modulator.stop(now + 0.5);
            carrier.stop(now + 0.5);

            // Push visualizer HUD bars to extreme heights
            animateHUD();
        }

        // Animate the visualizer HUD bars in response to the squeal
        function animateHUD() {
            const bars = document.querySelectorAll('.eq-bar');
            bars.forEach((bar) => {
                const heightVal = Math.floor(Math.random() * 25 + 15);
                bar.style.height = `${heightVal}px`;
                bar.style.backgroundColor = 'var(--accent)';
                
                // Return to idle state
                setTimeout(() => {
                    bar.style.height = '4px';
                    bar.style.backgroundColor = 'var(--cyber-blue)';
                }, 500);
            });
        }


        // --- 3. SNOUT 🐽 EMOJI SNORT GENERATION ---
        function triggerSnoutEmojiExplosion() {
            const chamber = document.querySelector('.chamber-stage');
            const rect = chamber.getBoundingClientRect();
            
            // Focus snout center
            const startX = rect.left + rect.width / 2;
            const startY = rect.top + rect.height / 2 - 25;

            for (let i = 0; i < 8; i++) {
                const snoutParticle = document.createElement('div');
                snoutParticle.className = 'snout-emoji-particle';
                snoutParticle.innerText = '🐽';
                snoutParticle.style.left = `${startX}px`;
                snoutParticle.style.top = `${startY}px`;
                document.body.appendChild(snoutParticle);

                const angle = (Math.random() * -Math.PI) - 0.2; // project upwards
                const speedDist = Math.random() * 100 + 60;
                const destinationX = Math.cos(angle) * speedDist;
                const destinationY = Math.sin(angle) * speedDist - 40;

                snoutParticle.animate([
                    { transform: 'translate(0, 0) scale(0.3) rotate(0deg)', opacity: 1 },
                    { transform: `translate(${destinationX}px, ${destinationY}px) scale(1.5) rotate(${Math.random() * 60 - 30}deg)`, opacity: 0 }
                ], {
                    duration: 1200 + Math.random() * 300,
                    easing: 'ease-out',
                    fill: 'forwards'
                });

                setTimeout(() => snoutParticle.remove(), 1600);
            }
        }


        // --- 4. DYNAMIC DECK INTERACTIVE ACTIONS ---
        function triggerPigOinkSqueal(e) {
            if (e) e.stopPropagation();

            // Play loud synthesized squeal
            synthesizeSqueal();

            // Trigger reactive shockwave ripple ring
            const wave = document.getElementById('shockwave');
            wave.style.animation = 'none';
            void wave.offsetWidth; // Force Reflow
            wave.style.animation = 'rippleExplode 0.6s cubic-bezier(0.1, 0.8, 0.3, 1) forwards';

            // Shake Interface Grid slightly to simulate loudness physical impact!
            const deck = document.getElementById('tilt-container');
            deck.animate([
                { transform: 'translate(1px, 1px) rotate(0deg)' },
                { transform: 'translate(-1px, -2px) rotate(-1deg)' },
                { transform: 'translate(-3px, 0px) rotate(1deg)' },
                { transform: 'translate(0px, 2px) rotate(0deg)' },
                { transform: 'translate(1px, -1px) rotate(1deg)' },
                { transform: 'translate(0px, 0px) rotate(0deg)' }
            ], { duration: 400 });

            // Apply 3D bouncy jump & snout shake animations
            const character = document.getElementById('pig-character');
            const snout = document.getElementById('pig-snout');

            character.classList.remove('oink-jump');
            snout.classList.remove('snout-wiggle');
            void character.offsetWidth; // Reflow
            void snout.offsetWidth;

            character.classList.add('oink-jump');
            snout.classList.add('snout-wiggle');

            // Generate Snout Emojis
            triggerSnoutEmojiExplosion();

            // Spawn firework sparks on Canvas
            const rect = document.querySelector('.chamber-stage').getBoundingClientRect();
            const clickX = rect.left + rect.width / 2;
            const clickY = rect.top + rect.height / 2 - 25;
            for (let i = 0; i < 30; i++) {
                explosions.push(new Spark(clickX, clickY));
            }
        }

        // AUTO-TRIGGER INITIAL WELCOME ENTRANCE JUMP
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                triggerPigOinkSqueal(null);
            }, 1900); // synchronizes perfectly with the bouncy entry landing
        });


        // --- 5. HIGH-PERFORMANCE 60 FPS PARTY CANVAS ---
        const canvas = document.getElementById('confetti-canvas');
        const ctx = canvas.getContext('2d');
        const confettiSlider = document.getElementById('confetti-slider');
        const confettiLabel = document.getElementById('confetti-label');

        let particles = [];
        let explosions = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        const palette = [
            { light: '#a78bfa', dark: '#8b5cf6' }, // Purple
            { light: '#f472b6', dark: '#ec4899' }, // Pink
            { light: '#22d3ee', dark: '#06b6d4' }, // Cyber Blue
            { light: '#34d399', dark: '#059669' }  // Cyber Green
        ];

        // Confetti Engine
        class Confetti {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = -20 - (Math.random() * 100);
                this.size = Math.random() * 8 + 8;
                this.color = palette[Math.floor(Math.random() * palette.length)];
                
                const speedMult = parseFloat(confettiSlider.value) / 2.0;
                this.speedY = (Math.random() * 3 + 2) * speedMult;
                this.speedX = (Math.random() * 1.5 - 0.75) * speedMult;
                
                this.rotX = Math.random() * 360;
                this.rotY = Math.random() * 360;
                this.rotSpeedX = Math.random() * 3 + 2;
                this.rotSpeedY = Math.random() * 3 + 2;
            }

            update() {
                const speedMult = parseFloat(confettiSlider.value) / 2.0;
                this.y += this.speedY * speedMult;
                this.x += (this.speedX + Math.sin(this.y / 30) * 0.4) * speedMult;
                this.rotX += this.rotSpeedX;
                this.rotY += this.rotSpeedY;
            }

            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                const cosY = Math.cos(this.rotY * Math.PI / 180);
                ctx.scale(cosY, Math.sin(this.rotX * Math.PI / 180));
                ctx.fillStyle = cosY > 0 ? this.color.light : this.color.dark;
                ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
                ctx.restore();
            }
        }

        // Explosive tap sparkles
        class Spark {
            constructor(x, y) {
                this.x = x;
                this.y = y;
                this.color = palette[Math.floor(Math.random() * palette.length)].light;
                this.size = Math.random() * 5 + 3;
                
                const angle = Math.random() * Math.PI * 2;
                const speed = Math.random() * 7 + 4;
                this.vx = Math.cos(angle) * speed;
                this.vy = Math.sin(angle) * speed;
                
                this.gravity = 0.16;
                this.opacity = 1;
                this.decay = Math.random() * 0.02 + 0.02;
            }

            update() {
                this.vy += this.gravity;
                this.x += this.vx;
                this.y += this.vy;
                this.opacity -= this.decay;
            }

            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        // Adjust labels based on sliding intensity state
        confettiSlider.addEventListener('input', () => {
            const val = parseFloat(confettiSlider.value);
            if (val <= 1.0) confettiLabel.innerText = "SLOW TEMPO";
            else if (val <= 2.0) confettiLabel.innerText = "STANDARD";
            else if (val <= 3.0) confettiLabel.innerText = "OVERDRIVE";
            else confettiLabel.innerText = "MAX PARTY HYPERMODE ⚡";
        });

        // Initialize rain
        function initCelebrations() {
            for (let i = 0; i < 70; i++) {
                setTimeout(() => {
                    particles.push(new Confetti());
                }, i * 40);
            }
        }

        function animateLoop() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Render background rain
            particles.forEach((p, index) => {
                p.update();
                p.draw();
                if (p.y > canvas.height + 20) {
                    particles[index] = new Confetti();
                }
            });

            // Render explosive clicks sparks
            explosions.forEach((s, index) => {
                s.update();
                s.draw();
                if (s.opacity <= 0) {
                    explosions.splice(index, 1);
                }
            });

            requestAnimationFrame(animateLoop);
        }

        initCelebrations();
        animateLoop();
    </script>
</body>
</html>