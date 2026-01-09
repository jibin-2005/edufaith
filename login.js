import { auth, googleProvider } from "./firebase_config.js";
import { signInWithEmailAndPassword, signInWithPopup } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> 7e1952f (09/01/2026)
const roles = document.querySelectorAll('.role');
const roleInput = document.getElementById('selectedRole');
const loginForm = document.querySelector('form');
const googleBtn = document.getElementById('googleBtn');

// Role Selection Logic
roles.forEach(role => {
    role.addEventListener('click', () => {
        roles.forEach(r => r.classList.remove('active'));
        role.classList.add('active');
        roleInput.value = role.getAttribute('data-role');
    });
});

// Bridge to PHP Backend
async function bridgeToBackend(user) {
    const token = await user.getIdToken();
    const email = user.email;

    // Send to PHP to check MySQL and start Session
    fetch('firebase_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email: email, token: token })
    })
        .then(async res => {
            const text = await res.text();
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert("Login Failed: " + data.message);
                }
            } catch (e) {
                console.error("Server Error:", text);
                alert("Backend Error (MySQL Bridge):\n" + text.substring(0, 500));
            }
        })
        .catch(err => {
            alert("Network Error: " + err.message);
        });
}

// 1. Email/Password Login (Replaces default form submit)
loginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = loginForm.email.value;
    const password = loginForm.password.value;
    const submitBtn = loginForm.querySelector('button');

    submitBtn.innerText = "Logging in...";
    submitBtn.disabled = true;

    signInWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
            // Signed in via Firebase
            bridgeToBackend(userCredential.user);
        })
        .catch((error) => {
            console.error("Firebase Login Error", error);
            alert("Login Failed: " + error.message);
            submitBtn.innerText = "Login";
            submitBtn.disabled = false;
        });
});

// 2. Google Login (Popup)
// Button is now in HTML, specific ID is customGoogleBtn
const customGoogleBtn = document.getElementById('customGoogleBtn');

if (customGoogleBtn) {
    console.log("Attaching Google Sign-In Listener");
    customGoogleBtn.addEventListener('click', () => {
        console.log("Google Button Clicked");
        // alert("Starting Google Sign-In..."); // Uncomment if needed for visual confirmation

        signInWithPopup(auth, googleProvider)
            .then((result) => {
                console.log("Google Sign-In Success", result.user.email);
                bridgeToBackend(result.user);
            }).catch((error) => {
                console.error("Google Sign-In Error", error);
                alert("Google Sign In Error: " + error.message);
            });
    });
} else {
    console.error("Google Button not found in DOM");
<<<<<<< HEAD
=======
=======
const loginForm = document.getElementById('loginForm');
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

    // Set the hidden role input just in case (though SQL handles it)
    const roleInput = document.getElementById('selectedRole');
    if (roleInput) roleInput.value = roleParam.toLowerCase();
}

// Bridge to PHP Backend
async function bridgeToBackend(user) {
    try {
        const token = await user.getIdToken();
        const email = user.email;

        const response = await fetch('firebase_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, token: token })
        });

        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert("Account Error: " + data.message);
            }
        } catch (e) {
            console.error("Server Error:", text);
            alert("Backend Error (MySQL Bridge):\n" + text.substring(0, 500));
        }
    } catch (err) {
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

            // B. Fallback to SQL Database
            if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password' || error.code === 'auth/invalid-credential' || error.code === 'auth/invalid-email') {
                console.log("Attempting SQL Database Fallback...");
                submitBtn.innerText = "Logging in via Database...";
                loginForm.submit();
            } else {
                alert("Login Failed: " + error.message);
                submitBtn.innerText = "Login";
                submitBtn.disabled = false;
            }
        }
    });
}

// 2. Google Login (Popup)
const customGoogleBtn = document.getElementById('customGoogleBtn');
if (customGoogleBtn) {
    customGoogleBtn.addEventListener('click', async () => {
        try {
            const result = await signInWithPopup(auth, googleProvider);
            await bridgeToBackend(result.user);
        } catch (error) {
            console.error("Google Sign-In Error", error);
            alert("Google Sign In Error: " + error.message);
        }
    });
>>>>>>> 85623df (Initial commit - Sunday School Management System)
>>>>>>> 7e1952f (09/01/2026)
}
