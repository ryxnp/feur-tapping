// Function to update the current time every second
function updateTime() {
    const now = new Date();
    const options = { 
        weekday: 'short', year: 'numeric', month: 'long', day: 'numeric', 
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
    };
    
    document.getElementById('currentTime').innerHTML = now.toLocaleString('en-US', options);
}

// Update the time immediately and then every second
updateTime();
setInterval(updateTime, 1000);

// Function to remove alert and clear image after 5 seconds
function removeAlertAndImage() {
    const alertBox = document.getElementById('alertBox');
    const profileImage = document.getElementById('profileImage');
    
    if (alertBox) {
        setTimeout(() => {
            alertBox.classList.add('fade'); // Optional fade effect (requires CSS)
            alertBox.style.display = 'none'; // Remove from view after delay
            
            // Clear the image as well
            profileImage.src = 'assets/blank.png'; // Clear the image source
        }, 5000); // Wait for 5 seconds before removing
    }
}

// Call removeAlertAndImage function if there is an alert box present
removeAlertAndImage();