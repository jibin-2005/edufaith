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
            let firebaseUid = null;
            try {
                const userCredential = await createUserWithEmailAndPassword(secondaryAuth, email, password);
                const user = userCredential.user;
                firebaseUid = user.uid;
                console.log("Firebase account created:", firebaseUid);
                // Sign out new user
                await signOut(secondaryAuth);
            } catch (authError) {
                if (authError.code === 'auth/email-already-in-use') {
                    console.warn("User already exists in Firebase. Proceeding to sync with DB.");
                    // We don't have the UID, but we can't get it without login. 
                    // Since backend doesn't require UID for INSERT, we proceed.
                } else {
                    throw authError; // Rethrow other errors
                }
            }

            // 3. Send to MySQL via PHP
            submitBtn.innerText = "Saving to Database...";
            const formData = new FormData();
            formData.append('fullname', fullname);
            formData.append('email', email);
            formData.append('password', password); // We hash it in PHP
            formData.append('role', role);
            if (firebaseUid) {
                formData.append('firebase_uid', firebaseUid);
            }
            if (addForm.class_id && addForm.class_id.value) {
                formData.append('class_id', addForm.class_id.value);
            }

            const response = await fetch('../includes/add_user_process.php', {
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
