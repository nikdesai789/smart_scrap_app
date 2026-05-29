// Image Preview
function previewImages(input) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }
}

// Get User Location
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        });
    }
}

// Calculate Distance
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            alert(`Please fill ${input.name || input.id} field`);
            input.focus();
            return false;
        }
    }
    return true;
}

// AJAX Request Helper
async function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

// Time Slot Filter
function filterTimeSlots(selectedDate) {
    const stakeholderId = document.getElementById('stakeholder_id').value;
    
    fetch(`get_availability.php?date=${selectedDate}&stakeholder_id=${stakeholderId}`)
        .then(response => response.json())
        .then(data => {
            const timeSlotSelect = document.getElementById('pickup_time');
            timeSlotSelect.innerHTML = '<option value="">Select Time Slot</option>';
            
            data.slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.start_time;
                option.textContent = `${slot.start_time} - ${slot.end_time}`;
                timeSlotSelect.appendChild(option);
            });
        });
}
// Toggle transaction ID field based on payment method
function toggleTransactionId(value) {
    const div = document.getElementById('transaction_div');
    if (div) {
        div.style.display = value === 'upi' ? 'block' : 'none';
    }
}

// Calculate final amount
function calculateAmount(weight, pricePerKg) {
    const amount = weight * pricePerKg;
    const amountField = document.getElementById('final_amount');
    const hiddenField = document.getElementById('amount_hidden');
    
    if (amountField) {
        amountField.value = '₹' + amount.toFixed(2);
    }
    if (hiddenField) {
        hiddenField.value = amount;
    }
}

// Filter stakeholders by distance
function filterStakeholdersByDistance(maxDistance) {
    const cards = document.querySelectorAll('.stakeholder-card');
    cards.forEach(card => {
        const distanceElem = card.querySelector('.distance');
        if (distanceElem) {
            const distance = parseFloat(distanceElem.dataset.distance);
            if (distance > maxDistance) {
                card.style.display = 'none';
            } else {
                card.style.display = 'block';
            }
        }
    });
}

// Auto-refresh pending requests (every 30 seconds)
function autoRefreshRequests() {
    if (document.querySelector('.pending-requests-container')) {
        setInterval(() => {
            fetch('check_new_requests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.has_new) {
                        location.reload();
                    }
                });
        }, 30000);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    autoRefreshRequests();
    
    // Add distance filter if on stakeholder list page
    const distanceFilter = document.getElementById('distance-filter');
    if (distanceFilter) {
        distanceFilter.addEventListener('change', function() {
            filterStakeholdersByDistance(parseFloat(this.value));
        });
    }
});