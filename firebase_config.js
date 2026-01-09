// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getAuth, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-analytics.js";

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyDTmypL1UgAxisjYrm9dmBjrcO7yp8dKJ8",
    authDomain: "sunday-219fa.firebaseapp.com",
    projectId: "sunday-219fa",
    storageBucket: "sunday-219fa.firebasestorage.app",
    messagingSenderId: "102488394492",
    appId: "1:102488394492:web:eac28db0be612b5e4b2579",
    measurementId: "G-360N2N5YNC"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const googleProvider = new GoogleAuthProvider();
const analytics = getAnalytics(app);

export { app, auth, googleProvider, analytics };
