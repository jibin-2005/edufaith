import { db } from './firebase_config.js';
import {
    doc,
    setDoc,
    onSnapshot,
    collection,
    query,
    where,
    getDocs,
    serverTimestamp
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

const RealTimeSync = {
    /**
     * Triggers a sync event in Firestore to notify listeners.
     * @param {string} collectionName - e.g., 'attendance_updates', 'leave_updates', 'result_updates'
     * @param {object} data - Payload for the trigger (e.g., student_id, class_id)
     */
    async triggerSync(collectionName, data = {}) {
        try {
            const syncDocRef = doc(collection(db, collectionName), 'latest');
            await setDoc(syncDocRef, {
                ...data,
                timestamp: serverTimestamp()
            });
            console.log(`[RealTimeSync] Triggered ${collectionName}`);
        } catch (error) {
            console.error(`[RealTimeSync] Trigger error:`, error);
        }
    },

    /**
     * Listens for changes in a specific sync document.
     * @param {string} collectionName 
     * @param {function} callback 
     */
    listen(collectionName, callback) {
        return onSnapshot(doc(db, collectionName, 'latest'), (docSnap) => {
            if (docSnap.exists()) {
                callback(docSnap.data());
            }
        });
    },

    /**
     * Checks URL parameters for success messages and triggers sync if found.
     */
    checkAndTriggerFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');

        if (msg === 'saved' || msg === 'success') {
            // Determine what to trigger based on context (URL or current page)
            const path = window.location.pathname;

            if (path.includes('save_attendance.php') || path.includes('attendance')) {
                const classId = urlParams.get('class_id');
                this.triggerSync('attendance_updates', { class_id: classId });
            }
            else if (path.includes('manage_leaves.php')) {
                this.triggerSync('leave_updates', { type: 'approval' });
            }
            else if (path.includes('manage_results.php')) {
                const studentId = urlParams.get('student_id');
                this.triggerSync('result_updates', { student_id: studentId });
            }
            else if (path.includes('manage_students.php') || path.includes('manage_teachers.php')) {
                this.triggerSync('user_updates', { type: 'count_change' });
            }
        }
    }
};

export default RealTimeSync;
