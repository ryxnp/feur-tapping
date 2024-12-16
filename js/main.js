// Function to clear alerts and reset fields after a timeout
function clearFieldsAndAlerts() {
    // Clear alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => alert.classList.remove('show'));
    
    setTimeout(() => {
        alerts.forEach(alert => alert.remove()); // Remove from DOM after fade out
        document.getElementById('profileImage').src = 'assets/default-profile.png'; // Reset image to default
        document.getElementById('infoInput').value = ''; // Clear input field

        // Clear employee information display (if any)
        const employeeInfoDivs = document.querySelectorAll('.big-box div');
        employeeInfoDivs.forEach(div => div.innerHTML = '');
        
        document.getElementById('currentTime').innerText = ''; // Optionally clear the current time display as well.
        
     }, 500);  // Delay before clearing fields for visual effect

}

// Automatically clear alerts and fields after 5 seconds of page load or action.
setTimeout(clearFieldsAndAlerts, 5000); // Adjust time as needed (5000 ms = 5 seconds)