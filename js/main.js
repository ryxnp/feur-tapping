// Function to clear alerts and reset fields after a timeout
function clearFieldsAndAlerts() {
    // Clear alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => alert.classList.remove('show'));
    
    setTimeout(() => {
        alerts.forEach(alert => alert.remove()); // Remove from DOM after fade out
        
        // Reset image to default
        document.getElementById('profileImage').src = 'assets/default-profile.png'; 
        
        // Clear input field
        document.getElementById('infoInput').value = ''; 

        // Clear only the contents of .big-box
        const bigBox = document.querySelector('.big-box');
        if (bigBox) {
            bigBox.innerHTML = ''; // Clear all content in big-box
        }

        // Clear employee information - ensure this section is targeted correctly
        const employeeInfoDiv = document.querySelector('.big-box .text-center');
        if (employeeInfoDiv) {
            employeeInfoDiv.innerHTML = ''; // Clear employee info display
        }

        // Optionally, you may want to keep the current time display intact
        const currentTimeDisplay = document.getElementById('currentTime');
        if (currentTimeDisplay) {
            currentTimeDisplay.textContent = 'Loading current time...'; // Reset message if needed
        }
     }, 500);  // Delay before clearing fields for visual effect
}

// Automatically clear alerts and fields after 5 seconds of page load or action.
setTimeout(clearFieldsAndAlerts, 5000); // Adjust time as needed (5000 ms = 5 seconds)

window.onload = function() {
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
