            </div> <!-- End content-container -->
        </div> <!-- End smooth-content -->
    </main>

    <!-- The Interactive Gummy Mascot Container -->
    <div id="mascot-container">
        <div id="mascot-wrap" style="
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            cursor: grab;
            user-select: none;
            perspective: 600px;
        ">
            <svg id="mascot-svg" viewBox="0 0 160 160" style="width: 125px; height: 125px; display: block; overflow: visible; filter: drop-shadow(0 10px 25px rgba(0,0,0,0.75)) drop-shadow(0 0 12px rgba(93,219,133,0.3));">
                <defs>
                    <!-- Skin Gradient -->
                    <linearGradient id="gummy-skin" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#fff8f2"/>
                        <stop offset="60%" stop-color="#feede4"/>
                        <stop offset="100%" stop-color="#fcd8cb"/>
                    </linearGradient>
                    <!-- Dark Forest Green Hair Gradient -->
                    <linearGradient id="gummy-hair" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2a4024"/>
                        <stop offset="45%" stop-color="#1f321a"/>
                        <stop offset="100%" stop-color="#121e10"/>
                    </linearGradient>
                    <!-- Hair Highlight Subtle -->
                    <linearGradient id="gummy-hair-hi" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3f5e35"/>
                        <stop offset="100%" stop-color="#23381d"/>
                    </linearGradient>
                    <!-- Matcha Horn Gradient -->
                    <linearGradient id="gummy-horn-grad" x1="0" y1="0" x2="0.6" y2="1">
                        <stop offset="0%" stop-color="#d6ff6b"/>
                        <stop offset="45%" stop-color="#a4db37"/>
                        <stop offset="100%" stop-color="#699e1c"/>
                    </linearGradient>
                    <!-- Emerald Eye Gradient -->
                    <linearGradient id="gummy-iris" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#102611"/>
                        <stop offset="35%" stop-color="#1b451d"/>
                        <stop offset="75%" stop-color="#489e2c"/>
                        <stop offset="100%" stop-color="#80d948"/>
                    </linearGradient>
                </defs>

                <!-- ═══════════════════════════════════════ -->
                <!--  EYE CLIPPING PATHS FOR 2-WAY BLINKING  -->
                <!-- ═══════════════════════════════════════ -->
                <clipPath id="eye-clip-l">
                    <rect id="mascot-eye-clip-l" x="40" y="96" width="30" height="30" rx="13" style="transform-origin: 55px 111px;"/>
                </clipPath>
                <clipPath id="eye-clip-r">
                    <rect id="mascot-eye-clip-r" x="90" y="96" width="30" height="30" rx="13" style="transform-origin: 105px 111px;"/>
                </clipPath>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 1: BACK HAIR / VOLUME          -->
                <!-- ═══════════════════════════════════════ -->
                <!-- Top & Side Hair mass -->
                <path d="M 22 45 
                         C 22 28 35 24 80 24 
                         C 125 24 138 28 138 45 
                         L 138 120 
                         C 138 138 126 142 110 142
                         L 50 142
                         C 34 142 22 138 22 120
                         Z" 
                      fill="url(#gummy-hair)"/>

                <!-- Side hair locks (rambut acak rapi samping) -->
                <path d="M 20 60 Q 12 75 22 88 Q 14 98 24 112 Q 18 122 26 130 Z" fill="url(#gummy-hair)" stroke="#0a140b" stroke-width="2"/>
                <path d="M 140 60 Q 148 75 138 88 Q 146 98 136 112 Q 142 122 134 130 Z" fill="url(#gummy-hair)" stroke="#0a140b" stroke-width="2"/>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 2: MATCHA HORNS (2 TANDUK KECIL) -->
                <!-- ═══════════════════════════════════════ -->
                <!-- Left Horn -->
                <g id="mascot-horn-l">
                    <path d="M 34 50 
                             C 24 45 15 32 20 18 
                             C 28 20 38 28 42 42 
                             Z" 
                          fill="url(#gummy-horn-grad)" stroke="#0a140b" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M 23 23 Q 28 28 32 38" stroke="#efffa8" stroke-width="1.8" stroke-linecap="round" fill="none" opacity="0.85"/>
                </g>

                <!-- Right Horn -->
                <g id="mascot-horn-r">
                    <path d="M 126 50 
                             C 136 45 145 32 140 18 
                             C 132 20 122 28 118 42 
                             Z" 
                          fill="url(#gummy-horn-grad)" stroke="#0a140b" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M 137 23 Q 132 28 128 38" stroke="#efffa8" stroke-width="1.8" stroke-linecap="round" fill="none" opacity="0.85"/>
                </g>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 3: EARS & FACE SKIN (KOTAK BAWAH) -->
                <!-- ═══════════════════════════════════════ -->
                <!-- Ears (Simetris cx=26 dan cx=134 dengan outline rapi) -->
                <ellipse id="mascot-ear-l" cx="26" cy="116" rx="9" ry="12" fill="url(#gummy-skin)" stroke="#0a140b" stroke-width="3"/>
                <ellipse id="mascot-ear-r" cx="134" cy="116" rx="9" ry="12" fill="url(#gummy-skin)" stroke="#0a140b" stroke-width="3"/>

                <!-- Boxy Face Skin (bawah kepala tetap kotak jelas) -->
                <path d="M 32 80 
                         L 128 80 
                         L 128 126 
                         C 128 138 120 144 108 144 
                         L 52 144 
                         C 40 144 32 138 32 126 
                         Z" 
                      fill="url(#gummy-skin)" stroke="#0a140b" stroke-width="3.5" stroke-linejoin="round"/>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 4: EYES (MATA BESAR & SAYU / SLEEPY) -->
                <!-- ═══════════════════════════════════════ -->
                <!-- Eyebrows (Tegas & Calm/Cool) -->
                <path d="M 45 92 Q 56 89 67 93" stroke="#162914" stroke-width="3" stroke-linecap="round" fill="none"/>
                <path d="M 115 92 Q 104 89 93 93" stroke="#162914" stroke-width="3" stroke-linecap="round" fill="none"/>

                <!-- LEFT EYE -->
                <g id="mascot-eye-l">
                    <g clip-path="url(#eye-clip-l)">
                        <rect x="42" y="97" width="26" height="28" rx="13" fill="#0d1c0e" stroke="#0a140b" stroke-width="2"/>
                        <g id="mascot-pupil-l">
                            <rect x="44" y="99" width="22" height="24" rx="11" fill="url(#gummy-iris)"/>
                            <ellipse cx="55" cy="116" rx="8" ry="4" fill="#88e84a" opacity="0.9"/>
                            <circle cx="50" cy="104" r="4.2" fill="#ffffff"/>
                            <circle cx="60" cy="116" r="2" fill="#ffffff"/>
                        </g>
                    </g>
                    <path id="mascot-hood-l" d="M 40 98 Q 55 93 70 99" stroke="#0a140b" stroke-width="4.5" stroke-linecap="round" fill="none"/>
                </g>

                <!-- RIGHT EYE -->
                <g id="mascot-eye-r">
                    <g clip-path="url(#eye-clip-r)">
                        <rect x="92" y="97" width="26" height="28" rx="13" fill="#0d1c0e" stroke="#0a140b" stroke-width="2"/>
                        <g id="mascot-pupil-r">
                            <rect x="94" y="99" width="22" height="24" rx="11" fill="url(#gummy-iris)"/>
                            <ellipse cx="105" cy="116" rx="8" ry="4" fill="#88e84a" opacity="0.9"/>
                            <circle cx="100" cy="104" r="4.2" fill="#ffffff"/>
                            <circle cx="110" cy="116" r="2" fill="#ffffff"/>
                        </g>
                    </g>
                    <path id="mascot-hood-r" d="M 90 99 Q 105 93 120 98" stroke="#0a140b" stroke-width="4.5" stroke-linecap="round" fill="none"/>
                </g>

                <!-- Soft Cheek Blush (Peach Hatch Lines) -->
                <g id="mascot-blush" opacity="0.85">
                    <line x1="43" y1="126" x2="47" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="49" y1="126" x2="53" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="55" y1="126" x2="59" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>

                    <line x1="101" y1="126" x2="105" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="107" y1="126" x2="111" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="113" y1="126" x2="117" y2="132" stroke="#ff7c7c" stroke-width="2.2" stroke-linecap="round"/>
                </g>

                <!-- Minimal Nose -->
                <circle cx="80" cy="122" r="1.2" fill="#d99382"/>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 5: MOUTH (DATAR IDLE -> SENYUM TAP) -->
                <!-- ═══════════════════════════════════════ -->
                <path id="mascot-mouth" 
                      d="M 74 130 Q 80 130 86 130" 
                      fill="#e65a50" 
                      stroke="#8a1f18" 
                      stroke-width="2.6" 
                      stroke-linecap="round" 
                      stroke-linejoin="round"/>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 6: FRONT HAIR & BANGS (PONI)  -->
                <!-- ═══════════════════════════════════════ -->
                <!-- Front Hair Crown & Bangs (Poni menutupi dahi secara rapi) -->
                <path d="M 22 55 
                         C 24 32 44 26 80 26 
                         C 116 26 136 32 138 55 
                         Q 132 78 126 64 
                         Q 118 88 110 68 
                         Q 102 96 96 74 
                         Q 88 106 80 82 
                         Q 72 106 64 74 
                         Q 58 96 50 68 
                         Q 42 88 34 64 
                         Q 28 78 22 55 
                         Z" 
                      fill="url(#gummy-hair)" stroke="#0a140b" stroke-width="3" stroke-linejoin="round"/>

                <!-- Subtle Hair Strands & Highlights -->
                <path d="M 64 74 Q 80 50 96 74" fill="none" stroke="#2f4a28" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M 45 52 Q 60 38 75 52" fill="none" stroke="#375730" stroke-width="2" stroke-linecap="round" opacity="0.6"/>

                <!-- ═══════════════════════════════════════ -->
                <!--  LAYER 7: AHOGE & LEAF CLIPS          -->
                <!-- ═══════════════════════════════════════ -->
                <!-- AHOGE (Jambul melengkung di atas kepala) -->
                <g id="mascot-ahoge" style="transform-origin: 80px 26px;">
                    <path d="M 75 26 
                             C 70 12 76 2 82 2 
                             C 88 2 92 10 85 26 
                             Z" 
                          fill="url(#gummy-hair)" stroke="#0a140b" stroke-width="2.5" stroke-linejoin="round"/>
                </g>

                <!-- LEAF CLIPS (2 Daun hijau di sisi kanan rambut) -->
                <g id="mascot-leaves" style="transform-origin: 126px 66px;">
                    <!-- Leaf 1 -->
                    <path d="M 120 62 
                             C 130 57 134 65 126 71 
                             C 118 69 116 64 120 62 
                             Z" 
                          fill="#8ee63e" stroke="#0a140b" stroke-width="2" stroke-linejoin="round"/>
                    <!-- Leaf 2 -->
                    <path d="M 128 70 
                             C 138 65 142 73 134 79 
                             C 126 77 124 72 128 70 
                             Z" 
                          fill="#aef054" stroke="#0a140b" stroke-width="2" stroke-linejoin="round"/>
                </g>
            </svg>
            <div id="mascot-sparkles" style="position:absolute; inset:0; pointer-events:none; overflow:visible;"></div>
        </div>

        <div id="mascot-dialog" style="
            position: fixed;
            bottom: 155px;
            right: 28px;
            background: rgba(14, 28, 16, 0.95);
            border: 1.5px solid rgba(140, 240, 120, 0.65);
            color: #e4fadc;
            padding: 10px 16px;
            border-radius: 16px 16px 4px 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            z-index: 1001;
            box-shadow: 0 8px 24px rgba(0,0,0,0.6), 0 0 14px rgba(93,219,133,0.3);
            max-width: 220px;
            text-align: center;
        ">
            Hi! Aku Gummy! 🌿
        </div>
    </div>

    <!-- Core App JS -->
    <script src="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/app.js'); ?>"></script>
    <script src="assets/js/mascot.js?v=<?php echo time(); ?>"></script>
    
    <script>
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
