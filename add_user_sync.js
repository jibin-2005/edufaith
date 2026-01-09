import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

// We need a SECONDARY Firebase app instance so we don't sign out the currently logged-in Admin
const firebaseConfig = {
    apiKey: "AIzaSyDTmypL1UgAxisjYrm9dmBjrcO7yp8dKJ8",
    authDomain: "sunday-219fa.firebaseapp.com",
    projectId: "sunday-219fa",
    storageBucket: "sunday-219fa.firebasestorage.app",
    messagingSenderId: "102488394492",
    appId: "1:102488394492:web:eac28db0be612b5e4b2579"
};

const secondaryApp = initializeApp(firebaseConfig, "Secondary");
const secondaryAuth = getAuth(secondaryApp);

const addForm = document.getElementById('addForm');
const statusMsg = document.getElementById('statusMsg');
const submitBtn = document.getElementById('submitBtn');

if (addForm) {
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fullname = addForm.fullname.value;
        const email = addForm.email.value;
        const password = addForm.password.value;
        const role = addForm.role.value;

        submitBtn.disabled = true;
        submitBtn.innerText = "Syncing with Firebase...";

        try {
            // 1. Create in Firebase Auth
            console.log("Creating Firebase account...");
            const userCredential = await createUserWithEmailAndPassword(secondaryAuth, email, password);
            const user = userCredential.user;
            console.log("Firebase account created:", user.uid);

            // 2. We immediately sign out the NEW user in the secondary instance 
            // so they don't accidentally become the active session for the admin
            await signOut(secondaryAuth);

            // 3. Send to MySQL via PHP
            submitBtn.innerText = "Saving to Database...";
            const formData = new FormData();
            formData.append('fullname', fullname);
            formData.append('email', email);
            formData.append('password', password); // We hash it in PHP
            formData.append('role', role);
            formData.append('firebase_uid', user.uid);

            const response = await fetch('add_user_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                window.location.href = result.redirect || 'manage_teachers.php?msg=success';
            } else {
                alert("Database Error: " + result.message);
                submitBtn.disabled = false;
                submitBtn.innerText = "Register Member";
            }

        } catch (error) {
            console.error("Firebase Sync Error", error);
            alert("Firebase Error: " + error.message);
            submitBtn.disabled = false;
            submitBtn.innerText = "Register Member";
        }
    });
}
