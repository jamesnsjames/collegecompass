function populateCourseDropdown() {
    fetch('fetch_courses.php')
        .then(response => response.json())
        .then(courses => {
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            // Clear existing courses
            dropdownMenu.innerHTML = '';
            
            // Add courses dynamically
            if (courses.length > 0) {
                courses.forEach(course => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.className = 'dropdown-item';
                    a.href = '#';
                    a.textContent = course.course_name;
                    
                    // Add course ID as a data attribute
                    a.dataset.courseId = course.id;
                    
                    // Add click event listener to filter colleges
                    a.addEventListener('click', function() {
                        filterCollegesByCourse(course.id);
                    });
                    
                    li.appendChild(a);
                    dropdownMenu.appendChild(li);
                });
            } else {
                // Fallback if no courses found
                const li = document.createElement('li');
                li.innerHTML = '<a class="dropdown-item disabled">No courses available</a>';
                dropdownMenu.appendChild(li);
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}









// Call the function when the page loads
document.addEventListener('DOMContentLoaded', function() {
    populateCourseDropdown();
});














// New function to filter colleges by course
function filterCollegesByCourse(courseId) {
    // Show loading spinner
    const container = document.getElementById('card-container');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading colleges...</p>
        </div>
    `;

    // Fetch colleges for the selected course
    fetch(`fetch_colleges_by_course.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(colleges => {
            // Use existing displayColleges function
            displayColleges(colleges);
        })
        .catch(error => {
            console.error('Error loading colleges:', error);
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h4>Error Loading Colleges</h4>
                    <p>Unable to fetch colleges for the selected course.</p>
                </div>
            `;
        });
}

// Existing code for displayColleges function remains the same
        // Global variable to store college data
        let collegeData = [];

        // Fetch colleges when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchColleges();

            // Setup search input event listener
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    filterColleges();
                }
            });

            // Handle college login form submission
            document.getElementById('collegeLoginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const collegeId = document.getElementById('collegeSelect').value;
                const password = document.getElementById('collegePassword').value;

                fetch('college_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        collegeId: collegeId,
                        password: password
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'college_dashboard.php?college=' + collegeId;
                    } else {
                        alert('Login failed: ' + (data.message || 'Invalid credentials'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Login failed due to technical error');
                });
            });
        });




        // Fetch colleges from PHP backend
        function fetchColleges() {
            fetch('fetch_colleges.php')
                .then(response => response.json())
                .then(data => {
                    collegeData = data;
                    displayColleges(data);
                    document.getElementById('loading-spinner').style.display = 'none';
                    // Initially call filterColleges to handle cases where the initial list is empty
                    filterColleges();
                })
                .catch(error => {
                    console.error('Error loading colleges:', error);
                    document.getElementById('loading-spinner').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load colleges. Please try again later.
                        </div>
                    `;
                    // In case of loading failure, ensure "No colleges found" is displayed if the data is empty
                    const container = document.getElementById('card-container');
                    container.innerHTML = `
                        <div class="col-12 text-center py-5" id="no-results-message">
                            <i class="fas fa-university fa-3x mb-3 text-muted"></i>
                            <h4>No colleges found</h4>
                            <p class="text-muted">Failed to load college data.</p>
                        </div>
                    `;
                });
        }

        // Display colleges in the UI
       // Display colleges in the UI
function displayColleges(colleges) {
    const container = document.getElementById('card-container');
    container.innerHTML = ''; // Clear existing cards

    if (colleges.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5" id="no-results-message">
                <i class="fas fa-university fa-3x mb-3 text-muted"></i>
                <h4>No colleges found</h4>
                <p class="text-muted">College data is currently empty.</p>
            </div>
        `;
        return;
    }

    // Debug - print the first college to see data structure
    console.log("First college data:", colleges[0]);

    colleges.forEach(college => {
        const col = document.createElement('div');
        col.className = 'col-md-3 college-card';
        col.dataset.name = college.name.toLowerCase();
        col.dataset.rating = college.rating;
        col.dataset.fees = college.fees;
        const imageName = college.name.toLowerCase(); // Keep spaces
        const imageSrc = `${imageName}.jpeg`; // Assuming .jpeg extension

        // Ensure fees is handled as a number
        const feesAmount = parseFloat(college.fees) || 0;

        col.innerHTML = `
            <div class="card h-100">
                <img src="${imageSrc}" class="card-img-top" alt="${college.name}">
                <div class="card-body">
                    <h5 class="card-title">${college.name}</h5>
                    <div class="mb-2">
                        <span class="badge bg-primary">Rating: ${college.rating}/5</span>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-success">
                            <i class="fas fa-rupee-sign me-1"></i> Fees: ₹${feesAmount.toLocaleString()}
                        </span>
                    </div>
                    
                    <a href="${college.name.toLowerCase().replace(/ /g, '')}details.html" class="btn btn-primary">
                        <i class="fas fa-info-circle me-1"></i> Details
                    </a>
                </div>
            </div>
        `;

        container.appendChild(col);
    });
    
    // After displaying, call filterColleges to handle initial filtering if any search term is present
    filterColleges();
}

        // Filter colleges based on search input
        function filterColleges() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.college-card');
            let found = false;
            const container = document.getElementById('card-container');
            let noResultsMessage = document.getElementById('no-results-message');

            cards.forEach(card => {
                const collegeName = card.dataset.name;
                if (collegeName.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                    found = true;
                } else {
                    card.style.display = 'none';
                }
            });

            if (!found) {
                if (!noResultsMessage) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'col-12 text-center py-5';
                    noResultsDiv.id = 'no-results-message';
                    noResultsDiv.innerHTML = `
                        <i class="fas fa-exclamation-circle fa-3x mb-3 text-muted"></i>
                        <h4>No colleges found</h4>
                        <p class="text-muted">Please try a different search term.</p>
                    `;
                    container.appendChild(noResultsDiv);
                    noResultsMessage = noResultsDiv; // Update the reference
                } else {
                    noResultsMessage.style.display = 'block';
                }
            } else {
                if (noResultsMessage) {
                    noResultsMessage.style.display = 'none';
                }
            }
        }

        // Sort colleges
        function sortColleges(method) {
            const container = document.getElementById('card-container');
            const cards = Array.from(document.querySelectorAll('.college-card'));

            cards.sort((a, b) => {
                if (method === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                } else if (method === 'rating') {
                    return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                }
                
                else if (method === 'fees') {
                    return parseFloat(b.dataset.fees) - parseFloat(a.dataset.fees);
                }
               
                
                
                
                return 0;
            });

            // Re-append sorted cards
            cards.forEach(card => container.appendChild(card));

            // Ensure "No colleges found" message is correctly displayed after sorting if no colleges match the current search term
            filterColleges();
        }
        // ... your existing JavaScript ...

    // Function to populate the college dropdown in the login modal
    function populateCollegeDropdown() {
        fetch('fetch_college_usernames.php')
            .then(response => response.json())
            .then(data => {
                const collegeSelect = document.getElementById('collegeSelect');
                collegeSelect.innerHTML = '<option value="">Select College</option>'; // Clear existing options

                data.forEach(college => {
                    const option = document.createElement('option');
                    option.value = college.college_id; // Use college_id as the value
                    option.textContent = college.username; // Display the username
                    collegeSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching college usernames:', error);
                // Optionally display an error message in the dropdown
                const collegeSelect = document.getElementById('collegeSelect');
                collegeSelect.innerHTML = '<option value="" disabled>Error loading colleges</option>';
            });
    }

    // Call this function when the modal is shown
    const collegeLoginModal = document.getElementById('collegeLoginModal');
    collegeLoginModal.addEventListener('show.bs.modal', populateCollegeDropdown);

    // Modify your college login form submission to send college_id and password
    document.getElementById('collegeLoginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const collegeId = document.getElementById('collegeSelect').value; // Get the selected college_id
        const password = document.getElementById('collegePassword').value;

        fetch('college_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                collegeId: collegeId, // Send college_id
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'college_dashboard.php?college=' + collegeId;
            } else {
                alert('Login failed: ' + (data.message || 'Invalid credentials'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Login failed due to technical error');
        });
    });

    // ... your other existing JavaScript ...
