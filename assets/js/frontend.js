/**
 * ArtitechCore Frontend Scripts
 * Handles native CTA form submissions and other public-facing logic.
 */
document.addEventListener("DOMContentLoaded", function() {
    // Native CTA Form Submission
    document.addEventListener("submit", function(e) {
        if (e.target && e.target.classList.contains("artitechcore-ce-native-form")) {
            e.preventDefault();
            
            var form = e.target;
            var submitBtn = form.querySelector(".artitechcore-ce-submit-btn");
            var responseDiv = form.querySelector(".artitechcore-ce-form-response");
            
            if (submitBtn.classList.contains("loading")) return;
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add("loading");
            
            if (responseDiv) {
                responseDiv.style.display = "none";
                responseDiv.className = "artitechcore-ce-form-response";
            }
            
            var formData = new FormData(form);
            
            fetch(artitechcore_frontend_data.ajaxurl, {
                method: "POST",
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success) {
                    if (responseDiv) {
                        responseDiv.className = "artitechcore-ce-form-response success";
                        responseDiv.textContent = res.data;
                        responseDiv.style.display = "block";
                    }
                    form.reset();
                } else {
                    if (responseDiv) {
                        responseDiv.className = "artitechcore-ce-form-response error";
                        responseDiv.textContent = res.data || "An error occurred. Please try again.";
                        responseDiv.style.display = "block";
                    }
                }
            })
            .catch(function() {
                if (responseDiv) {
                    responseDiv.className = "artitechcore-ce-form-response error";
                    responseDiv.textContent = "Connection error. Please try again.";
                    responseDiv.style.display = "block";
                }
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.classList.remove("loading");
            });
        }
    });
});
