// perlin-animation.js
import './perlin.js'; // Import the perlin.js library

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('auth-plasma');
    if (!canvas) {
        console.warn('Canvas with ID "auth-plasma" not found.');
        return;
    }
    const ctx = canvas.getContext('2d');

    let mouseX = -1000; // Initialize off-screen
    let mouseY = -1000;

    let animationFrameId;

    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas(); // Initial resize

    canvas.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    canvas.addEventListener('mouseleave', () => {
        mouseX = -1000; // Move mouse off-screen when not hovering
        mouseY = -1000;
    });

    // Initialize Perlin noise
    noise.seed(Math.random());

    const numPoints = 100;
    const baseRadiusX = 200;
    const baseRadiusY = 150;
    const noiseScale = 0.005;
    const noiseStrength = 50;
    const mouseRepelRadius = 150;
    const mouseRepelStrength = 50;
    const binaryCharSize = 12; // Size of '0' or '1'
    const binaryCharSpacing = 20; // Spacing between binary characters

    let time = 0;

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;

        ctx.beginPath();
        const points = [];

        for (let i = 0; i < numPoints; i++) {
            const angle = (i / numPoints) * Math.PI * 2;

            // Base oval shape
            let x = centerX + Math.cos(angle) * baseRadiusX;
            let y = centerY + Math.sin(angle) * baseRadiusY;

            // Perlin noise warping
            const noiseVal = noise.perlin2(x * noiseScale + time * 0.0005, y * noiseScale + time * 0.0005);
            x += noiseVal * noiseStrength * Math.cos(angle);
            y += noiseVal * noiseStrength * Math.sin(angle);

            // Mouse interaction
            const distToMouse = Math.sqrt(Math.pow(x - mouseX, 2) + Math.pow(y - mouseY, 2));
            if (distToMouse < mouseRepelRadius) {
                const repelAmount = (1 - distToMouse / mouseRepelRadius) * mouseRepelStrength;
                x += (x - mouseX) / distToMouse * repelAmount;
                y += (y - mouseY) / distToMouse * repelAmount;
            }

            points.push({ x, y });

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.closePath();

        // Draw the shape (optional, can be just border)
        // ctx.strokeStyle = 'rgba(0, 191, 255, 0.3)'; // Light blue, semi-transparent
        // ctx.lineWidth = 2;
        // ctx.stroke();

        // Draw binary border
        ctx.font = `${binaryCharSize}px monospace`;
        ctx.fillStyle = 'rgba(0, 191, 255, 0.8)'; // Light blue for binary chars

        let pathLength = 0;
        for (let i = 0; i < points.length; i++) {
            const p1 = points[i];
            const p2 = points[(i + 1) % points.length];
            pathLength += Math.sqrt(Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2));
        }

        let currentLength = 0;
        let charIndex = 0;
        for (let i = 0; i < points.length; i++) {
            const p1 = points[i];
            const p2 = points[(i + 1) % points.length];
            const segmentLength = Math.sqrt(Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2));

            let segmentProgress = 0;
            while (segmentProgress < segmentLength) {
                if (currentLength % binaryCharSpacing < binaryCharSize) { // Only draw if there's space
                    const t = segmentProgress / segmentLength;
                    const charX = p1.x + (p2.x - p1.x) * t;
                    const charY = p1.y + (p2.y - p1.y) * t;

                    const char = Math.random() > 0.5 ? '0' : '1';
                    ctx.fillText(char, charX, charY);
                }
                currentLength++;
                segmentProgress++;
            }
        }


        time++;
        animationFrameId = requestAnimationFrame(animate);
    }

    // Start animation only if canvas is available
    if (canvas) {
        animate();
    }
});