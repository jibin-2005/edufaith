import { auth, googleProvider } from "./firebase_config.js";
import { signInWithEmailAndPassword, signInWithPopup } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

const loginForm = document.getElementById('loginForm');
const customGoogleBtn = document.getElementById('customGoogleBtn');
const loginMainTitle = document.getElementById('loginMainTitle');
const loginSubtitle = document.getElementById('loginSubtitle');

// --- GRACEFUL ROLE TITLES ---
// Check URL for role parameter (e.g., login.html?role=admin)
const urlParams = new URLSearchParams(window.location.search);
const roleParam = urlParams.get('role');

if (roleParam) {
    const roleCapitalized = roleParam.charAt(0).toUpperCase() + roleParam.slice(1);
    if (loginMainTitle) loginMainTitle.innerText = `${roleCapitalized} Login`;
    if (loginSubtitle) loginSubtitle.innerText = `Access the ${roleCapitalized} Dashboard`;
}

// --- HYBRID AUTHENTICATION FLOW ---

// Bridge to PHP Backend
async function bridgeToBackend(user) {
    console.log("Starting bridge to MySQL backend for:", user.email);
    try {
        const token = await user.getIdToken();
        const email = user.email;

        // Send to PHP to check MySQL and start Session
        const response = await fetch('firebase_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                token: token,
                displayName: user.displayName || email.split('@')[0]
            })
        });

        const text = await response.text();
        console.log("Backend response received:", text);

        try {
            const data = JSON.parse(text);
            if (data.success) {
                console.log("Bridge success! Redirecting to:", data.redirect);
                window.location.href = data.redirect;
            } else {
                console.error("Bridge Error:", data.message);
                alert("Account Error: " + data.message);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e, text);
            alert("Backend Error (MySQL Bridge):\n" + text.substring(0, 500));
        }
    } catch (err) {
        console.error("Bridge Connection Error:", err);
        alert("Authentication Error: " + err.message);
    }
}

// 1. Email/Password Login (Hybrid Flow)
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = loginForm.email.value;
        const password = loginForm.password.value;
        const submitBtn = loginForm.querySelector('button');

        submitBtn.innerText = "Checking Authentication...";
        submitBtn.disabled = true;

        try {
            // A. Attempt Firebase Login first
            const userCredential = await signInWithEmailAndPassword(auth, email, password);
            console.log("Authenticated via Firebase");
            await bridgeToBackend(userCredential.user);
        } catch (error) {
            console.warn("Firebase Login Failed", error.code);

            // B. Fallback to SQL Database (Common for pre-existing users)
            if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password' || error.code === 'auth/invalid-credential' || error.code === 'auth/invalid-email') {
                console.log("Attempting SQL Database Fallback...");
                submitBtn.innerText = "Logging in via Database...";
                loginForm.submit(); // Standard POST to login_process.php
            } else {
                alert("Login Failed: " + error.message);
                submitBtn.innerText = "Login";
                submitBtn.disabled = false;
            }
        }
    });
}

// 2. Google Login (Popup)
if (customGoogleBtn) {
    customGoogleBtn.addEventListener('click', async () => {
        try {
            const result = await signInWithPopup(auth, googleProvider);
            console.log("Google Sign-In Success", result.user.email);
            await bridgeToBackend(result.user);
        } catch (error) {
            console.error("Google Sign-In Error", error);
            alert("Google Sign In Error: " + error.message);
        }
    });
}
