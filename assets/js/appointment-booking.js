/**
 * WellCare Connect - Appointment Booking JavaScript
 * Handles all appointment booking functionality including:
 * - Multi-step form navigation
 * - Calendar interactions
 * - Real-time availability checking
 * - Form validation
 * - Payment processing
 */

import '../styles/appointment.css';

// Doctor Search Alpine.js Component
function doctorSearch() {
    return {
        // Search query
        searchQuery: '',
        
        // Filters
        filters: {
            specialties: [],
            consultationTypes: [],
            maxPrice: 200,
            languages: [],
            gender: '',
            minRating: 0,
            location: '',
            availability: ''
        },
        
        // View mode
        viewMode: 'list',
        sortBy: 'recommended',
        
        // Loading state
        loading: false,
        
        // Doctors data
        doctors: [
            {
                id: 1,
                name: 'Sarah Johnson',
                specialty: 'Cardiology',
                rating: 4.9,
                reviewCount: 127,
                experience: 15,
                location: 'Tunis',
                languages: ['English', 'French', 'Arabic'],
                hospitals: ['Mustapha Hospital', 'Clinique Pasteur'],
                price: 150,
                nextAvailable: 'Today',
                isVerified: true,
                availableSlots: [
                    { date: '2026-02-03', time: '09:00' },
                    { date: '2026-02-03', time: '10:00' },
                    { date: '2026-02-03', time: '14:00' },
                    { date: '2026-02-04', time: '09:00' },
                    { date: '2026-02-04', time: '11:00' }
                ]
            },
            {
                id: 2,
                name: 'Michael Chen',
                specialty: 'Dermatology',
                rating: 4.8,
                reviewCount: 98,
                experience: 12,
                location: 'Sousse',
                languages: ['English', 'Arabic'],
                hospitals: ['Sousse Medical Center'],
                price: 120,
                nextAvailable: 'Tomorrow',
                isVerified: true,
                availableSlots: [
                    { date: '2026-02-04', time: '10:00' },
                    { date: '2026-02-04', time: '14:00' },
                    { date: '2026-02-05', time: '09:00' }
                ]
            },
            {
                id: 3,
                name: 'Emma Wilson',
                specialty: 'General Practice',
                rating: 4.7,
                reviewCount: 215,
                experience: 8,
                location: 'Tunis',
                languages: ['English', 'French', 'Arabic'],
                hospitals: ['City Health Clinic'],
                price: 80,
                nextAvailable: 'Today',
                isVerified: true,
                availableSlots: [
                    { date: '2026-02-03', time: '11:00' },
                    { date: '2026-02-03', time: '15:00' },
                    { date: '2026-02-03', time: '16:00' }
                ]
            },
            {
                id: 4,
                name: 'James Rodriguez',
                specialty: 'Neurology',
                rating: 4.9,
                reviewCount: 89,
                experience: 18,
                location: 'Sfax',
                languages: ['Spanish', 'French', 'Arabic'],
                hospitals: ['Sfax University Hospital'],
                price: 200,
                nextAvailable: 'This Week',
                isVerified: false,
                availableSlots: [
                    { date: '2026-02-06', time: '09:00' },
                    { date: '2026-02-07', time: '10:00' }
                ]
            },
            {
                id: 5,
                name: 'Lisa Thompson',
                specialty: 'Orthopedics',
                rating: 4.6,
                reviewCount: 156,
                experience: 14,
                location: 'Monastir',
                languages: ['English', 'Arabic'],
                hospitals: ['Monastir Regional Hospital'],
                price: 180,
                nextAvailable: 'Weekend',
                isVerified: true,
                availableSlots: [
                    { date: '2026-02-08', time: '09:00' },
                    { date: '2026-02-08', time: '10:00' },
                    { date: '2026-02-08', time: '11:00' }
                ]
            },
            {
                id: 6,
                name: 'David Kim',
                specialty: 'Pediatrics',
                rating: 4.8,
                reviewCount: 312,
                experience: 10,
                location: 'Nabeul',
                languages: ['Korean', 'English', 'French'],
                hospitals: ['Nabeul Children\'s Hospital'],
                price: 100,
                nextAvailable: 'Today',
                isVerified: true,
                availableSlots: [
                    { date: '2026-02-03', time: '08:00' },
                    { date: '2026-02-03', time: '09:00' },
                    { date: '2026-02-03', time: '10:00' },
                    { date: '2026-02-03', time: '13:00' }
                ]
            }
        ],
        
        // Popular specialties
        popularSpecialties: ['Cardiology', 'Dermatology', 'General Practice', 'Neurology', 'Orthopedics', 'Pediatrics'],
        
        // All specialties
        allSpecialties: ['Cardiology', 'Dermatology', 'General Practice', 'Neurology', 'Orthopedics', 'Pediatrics', 'Psychiatry', 'Ophthalmology', 'Gynecology', 'Urology'],
        
        // Languages
        languages: [
            { code: 'en', name: 'English' },
            { code: 'fr', name: 'French' },
            { code: 'ar', name: 'Arabic' },
            { code: 'es', name: 'Spanish' }
        ],
        
        // Pagination
        currentPage: 1,
        itemsPerPage: 10,
        
        // Initialize
        init() {
            console.log('Doctor search initialized');
            
            // Load doctors from server-rendered data
            const mainElement = document.querySelector('[data-doctors]');
            if (mainElement && mainElement.dataset.doctors) {
                try {
                    const serverDoctors = JSON.parse(mainElement.dataset.doctors);
                    if (Array.isArray(serverDoctors) && serverDoctors.length > 0) {
                        console.log('Replacing hardcoded doctors with ' + serverDoctors.length + ' doctors from server');
                        // Clear hardcoded doctors and add server doctors
                        this.doctors = [];
                        serverDoctors.forEach(doc => {
                            this.doctors.push(doc);
                        });
                        console.log('Doctors loaded successfully');
                    } else {
                        console.log('No doctors from server, keeping hardcoded data');
                    }
                } catch (e) {
                    console.error('Error parsing doctors data:', e);
                }
            } else {
                console.log('No data-doctors attribute found');
            }
        },
        
        // Computed: filtered doctors
        get filteredDoctors() {
            let result = this.doctors;
            
            // Search query filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(doc => 
                    doc.name.toLowerCase().includes(query) ||
                    doc.specialty.toLowerCase().includes(query)
                );
            }
            
            // Specialty filter
            if (this.filters.specialties.length > 0) {
                result = result.filter(doc => 
                    this.filters.specialties.includes(doc.specialty)
                );
            }
            
            // Price filter
            result = result.filter(doc => doc.price <= this.filters.maxPrice);
            
            // Language filter
            if (this.filters.languages.length > 0) {
                result = result.filter(doc => 
                    doc.languages.some(lang => this.filters.languages.includes(lang))
                );
            }
            
            // Rating filter
            result = result.filter(doc => doc.rating >= this.filters.minRating);
            
            // Location filter
            if (this.filters.location) {
                result = result.filter(doc => 
                    doc.location.toLowerCase() === this.filters.location.toLowerCase()
                );
            }
            
            // Sort
            result = this.sortDoctorsList(result);
            
            return result;
        },
        
        // Sort doctors
        sortDoctorsList(doctors) {
            return doctors.sort((a, b) => {
                switch (this.sortBy) {
                    case 'rating':
                        return b.rating - a.rating;
                    case 'experience':
                        return b.experience - a.experience;
                    case 'price-low':
                        return a.price - b.price;
                    case 'price-high':
                        return b.price - a.price;
                    case 'availability':
                        return a.nextAvailable.localeCompare(b.nextAvailable);
                    default:
                        return (b.rating * 10 + b.reviewCount) - (a.rating * 10 + a.reviewCount);
                }
            });
        },
        
        // Paginated doctors
        get paginatedDoctors() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredDoctors.slice(start, start + this.itemsPerPage);
        },
        
        // Apply filters
        applyFilters() {
            this.currentPage = 1;
        },
        
        // Reset filters
        resetFilters() {
            this.searchQuery = '';
            this.filters.specialties = [];
            this.filters.maxPrice = 200;
            this.filters.languages = [];
            this.filters.minRating = 0;
            this.filters.location = '';
            this.sortBy = 'recommended';
            this.currentPage = 1;
        },
        
        // Select specialty
        selectSpecialty(specialty) {
            this.searchQuery = specialty;
            this.applyFilters();
        },
        
        // Debounce search
        debounceTimer: null,
        debouncedSearch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.applyFilters();
            }, 300);
        },
        
        // Search doctors
        searchDoctors() {
            this.applyFilters();
        },
        
        // Book slot
        bookSlot(doctor, slot) {
            window.location.href = `/appointment/booking-flow?doctor=${doctor.id}&date=${slot.date}&time=${slot.time}`;
        }
    };
}

// Register with Alpine (only if not already defined inline)
import Alpine from 'alpinejs';
if (typeof window.doctorSearch !== 'function') {
    Alpine.data('doctorSearch', doctorSearch);
}

// Appointment Booking Module
const AppointmentBooking = {
    // Configuration
    config: {
        apiBaseUrl: '/api/appointments',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        dateFormat: 'YYYY-MM-DD',
        timeFormat: 'HH:mm'
    },

    // State
    state: {
        currentStep: 1,
        totalSteps: 4,
        formData: {},
        availableSlots: [],
        selectedDoctor: null,
        isLoading: false
    },

    /**
     * Initialize the appointment booking module
     */
    init() {
        this.setupEventListeners();
        this.initializeCalendar();
        this.loadDoctorData();
        console.log('Appointment Booking module initialized');
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Form navigation
        document.querySelectorAll('[data-step]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const step = parseInt(e.target.dataset.step);
                this.navigateToStep(step);
            });
        });

        // Consultation type selection
        document.querySelectorAll('[data-consultation-type]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectConsultationType(e.target.dataset.consultationType);
            });
        });

        // Appointment mode selection
        document.querySelectorAll('[data-appointment-mode]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectAppointmentMode(e.target.dataset.appointmentMode);
            });
        });

        // Real-time availability check
        const dateInputs = document.querySelectorAll('[data-availability-check]');
        dateInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.checkAvailability(e.target.value);
            });
        });
    },

    /**
     * Navigate to a specific step in the booking flow
     */
    navigateToStep(step) {
        if (step < 1 || step > this.state.totalSteps) return;
        
        // Validate current step before proceeding
        if (step > this.state.currentStep && !this.validateStep(this.state.currentStep)) {
            return;
        }

        this.state.currentStep = step;
        this.updateStepVisibility();
        this.updateProgressIndicator();
    },

    /**
     * Validate the current step
     */
    validateStep(step) {
        const stepElement = document.querySelector(`[data-step-content="${step}"]`);
        if (!stepElement) return true;

        const requiredFields = stepElement.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('input-error');
                
                // Add error message
                let errorMsg = field.parentElement.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'form-error';
                    errorMsg.textContent = 'This field is required';
                    field.parentElement.appendChild(errorMsg);
                }
            } else {
                field.classList.remove('input-error');
                const errorMsg = field.parentElement.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            }
        });

        return isValid;
    },

    /**
     * Update step visibility
     */
    updateStepVisibility() {
        document.querySelectorAll('[data-step-content]').forEach(el => {
            const step = parseInt(el.dataset.stepContent);
            if (step === this.state.currentStep) {
                el.classList.remove('hidden');
                el.classList.add('animate-fade-in');
            } else {
                el.classList.add('hidden');
                el.classList.remove('animate-fade-in');
            }
        });
    },

    /**
     * Update progress indicator
     */
    updateProgressIndicator() {
        document.querySelectorAll('[data-progress-step]').forEach(el => {
            const step = parseInt(el.dataset.progressStep);
            el.classList.remove('active', 'completed');
            
            if (step === this.state.currentStep) {
                el.classList.add('active');
            } else if (step < this.state.currentStep) {
                el.classList.add('completed');
            }
        });
    },

    /**
     * Initialize calendar
     */
    initializeCalendar() {
        const calendarEl = document.getElementById('appointment-calendar');
        if (!calendarEl) return;

        // Generate calendar days
        this.generateCalendarDays(new Date());
    },

    /**
     * Generate calendar days
     */
    generateCalendarDays(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        const calendarGrid = document.getElementById('calendar-grid');
        if (!calendarGrid) return;

        calendarGrid.innerHTML = '';

        // Empty slots for days before the first of the month
        for (let i = 0; i < firstDay; i++) {
            const emptySlot = document.createElement('div');
            emptySlot.className = 'calendar-day empty';
            calendarGrid.appendChild(emptySlot);
        }

        // Days of the month
        const today = new Date();
        for (let i = 1; i <= daysInMonth; i++) {
            const dayDate = new Date(year, month, i);
            const isPast = dayDate < new Date(today.setHours(0, 0, 0, 0));
            const isToday = dayDate.toDateString() === new Date().toDateString();
            
            const dayEl = document.createElement('button');
            dayEl.className = `calendar-day ${isPast ? 'disabled' : ''} ${isToday ? 'today' : ''}`;
            dayEl.textContent = i;
            
            if (!isPast) {
                dayEl.addEventListener('click', () => {
                    this.selectDate(dayDate);
                });
            }
            
            calendarGrid.appendChild(dayEl);
        }
    },

    /**
     * Handle date selection
     */
    selectDate(date) {
        this.state.formData.selectedDate = date;
        
        // Update UI
        document.querySelectorAll('.calendar-day').forEach(el => {
            el.classList.remove('selected');
        });
        event.target.classList.add('selected');
        
        // Load available time slots
        this.loadTimeSlots(date);
    },

    /**
     * Load available time slots for selected date
     */
    async loadTimeSlots(date) {
        const slotsContainer = document.getElementById('time-slots');
        if (!slotsContainer) return;

        this.showLoading(slotsContainer);

        try {
            // Mock API call - replace with actual endpoint
            const response = await fetch(`${this.config.apiBaseUrl}/available-slots`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: date.toISOString().split('T')[0],
                    doctorId: this.state.selectedDoctor?.id
                })
            });

            const slots = await response.json();
            this.state.availableSlots = slots;
            this.renderTimeSlots(slots);
        } catch (error) {
            console.error('Failed to load time slots:', error);
            this.showError(slotsContainer, 'Failed to load available times. Please try again.');
        }
    },

    /**
     * Render time slots
     */
    renderTimeSlots(slots) {
        const container = document.getElementById('time-slots');
        if (!container) return;

        container.innerHTML = '';

        slots.forEach(slot => {
            const slotEl = document.createElement('button');
            slotEl.className = `time-slot ${slot.available ? '' : 'booked'}`;
            slotEl.textContent = slot.time;
            
            if (slot.available) {
                slotEl.addEventListener('click', () => {
                    this.selectTimeSlot(slot);
                });
            }
            
            container.appendChild(slotEl);
        });
    },

    /**
     * Handle time slot selection
     */
    selectTimeSlot(slot) {
        this.state.formData.selectedTime = slot.time;
        
        // Update UI
        document.querySelectorAll('.time-slot').forEach(el => {
            el.classList.remove('selected');
        });
        event.target.classList.add('selected');
    },

    /**
     * Select consultation type
     */
    selectConsultationType(type) {
        this.state.formData.consultationType = type;
        
        // Update UI
        document.querySelectorAll('[data-consultation-type]').forEach(el => {
            el.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    },

    /**
     * Select appointment mode
     */
    selectAppointmentMode(mode) {
        this.state.formData.appointmentMode = mode;
        
        // Update UI
        document.querySelectorAll('[data-appointment-mode]').forEach(el => {
            el.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Update price display
        this.updatePriceDisplay(mode);
    },

    /**
     * Update price display based on mode
     */
    updatePriceDisplay(mode) {
        const prices = {
            'in-person': 120,
            'video': 90,
            'phone': 70
        };
        
        const priceEl = document.getElementById('consultation-price');
        if (priceEl) {
            priceEl.textContent = `${prices[mode]} TND`;
        }
    },

    /**
     * Check availability for a date
     */
    async checkAvailability(date) {
        try {
            const response = await fetch(`${this.config.apiBaseUrl}/check-availability`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ date })
            });
            
            const data = await response.json();
            this.updateAvailabilityIndicator(data.available);
        } catch (error) {
            console.error('Availability check failed:', error);
        }
    },

    /**
     * Update availability indicator
     */
    updateAvailabilityIndicator(available) {
        const indicator = document.getElementById('availability-indicator');
        if (!indicator) return;

        if (available) {
            indicator.className = 'availability-indicator available';
            indicator.textContent = 'Available';
        } else {
            indicator.className = 'availability-indicator unavailable';
            indicator.textContent = 'Not Available';
        }
    },

    /**
     * Load doctor data
     */
    async loadDoctorData() {
        const doctorId = new URLSearchParams(window.location.search).get('doctor');
        if (!doctorId) return;

        try {
            const response = await fetch(`/api/doctors/${doctorId}`);
            this.state.selectedDoctor = await response.json();
            this.renderDoctorInfo();
        } catch (error) {
            console.error('Failed to load doctor data:', error);
        }
    },

    /**
     * Render doctor information
     */
    renderDoctorInfo() {
        const doctor = this.state.selectedDoctor;
        if (!doctor) return;

        const nameEl = document.getElementById('doctor-name');
        const specialtyEl = document.getElementById('doctor-specialty');
        
        if (nameEl) nameEl.textContent = `Dr. ${doctor.name}`;
        if (specialtyEl) specialtyEl.textContent = doctor.specialty;
    },

    /**
     * Submit booking form
     */
    async submitBooking() {
        if (!this.validateStep(this.state.currentStep)) {
            return;
        }

        this.state.isLoading = true;
        this.showLoading(document.getElementById('booking-form'));

        try {
            const response = await fetch(`${this.config.apiBaseUrl}/book`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.state.formData)
            });

            const result = await response.json();
            
            if (result.success) {
                this.handleBookingSuccess(result);
            } else {
                this.handleBookingError(result);
            }
        } catch (error) {
            console.error('Booking failed:', error);
            this.handleBookingError({ message: 'Network error. Please try again.' });
        } finally {
            this.state.isLoading = false;
        }
    },

    /**
     * Handle successful booking
     */
    handleBookingSuccess(result) {
        // Store booking reference
        localStorage.setItem('lastBooking', JSON.stringify(result));
        
        // Redirect to confirmation page
        window.location.href = `/appointment/confirmation?id=${result.bookingId}`;
    },

    /**
     * Handle booking error
     */
    handleBookingError(result) {
        const errorContainer = document.getElementById('booking-errors');
        if (errorContainer) {
            errorContainer.textContent = result.message || 'Booking failed. Please try again.';
            errorContainer.classList.remove('hidden');
        }
    },

    /**
     * Show loading state
     */
    showLoading(element) {
        if (!element) return;
        element.classList.add('loading');
    },

    /**
     * Hide loading state
     */
    hideLoading(element) {
        if (!element) return;
        element.classList.remove('loading');
    },

    /**
     * Show error message
     */
    showError(element, message) {
        if (!element) return;
        element.innerHTML = `<p class="error-message">${message}</p>`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AppointmentBooking.init();
});

// Export for use in Alpine.js
window.AppointmentBooking = AppointmentBooking;

// Patient Dashboard Alpine.js Component
function patientDashboard() {
    return {
        // State
        activeTab: 'upcoming',
        appointments: [],
        upcomingAppointments: [],
        pendingAppointments: [],
        completedAppointments: [],
        pastAppointments: [],
        cancelledAppointments: [],
        
        // Counts
        upcomingCount: 0,
        pendingCount: 0,
        completedCount: 0,
        cancelledCount: 0,
        
        // Modal states
        showCancelModal: false,
        showReviewModal: false,
        showRescheduleModal: false,
        
        // Form data
        selectedAppointment: null,
        cancellationReason: '',
        reviewRating: 5,
        reviewComment: '',
        reviewDoctorName: '',
        reviewText: '',
        
        // Loading state
        loading: false,
        
        // Initialize
        async init() {
            await this.loadAppointments();
        },
        
        // Load appointments from API
        async loadAppointments() {
            this.loading = true;
            try {
                const response = await fetch('/appointment/api/appointments');
                const data = await response.json();
                
                console.log('API response:', data);
                
                // The API returns { upcoming: [], past: [], cancelled: [] }
                // We need to merge them and categorize
                this.appointments = [
                    ...(data.upcoming || []),
                    ...(data.past || []),
                    ...(data.cancelled || [])
                ];
                
                // The API already categorizes, so use directly
                this.upcomingAppointments = data.upcoming || [];
                this.pendingAppointments = data.pending || [];
                this.completedAppointments = data.past || [];
                this.pastAppointments = data.past || [];
                this.cancelledAppointments = data.cancelled || [];
                
                // Update counts
                this.upcomingCount = this.upcomingAppointments.length;
                this.pendingCount = this.pendingAppointments.length;
                this.completedCount = this.completedAppointments.length;
                this.cancelledCount = this.cancelledAppointments.length;
                
            } catch (error) {
                console.error('Failed to load appointments:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Categorize appointments by status
        categorizeAppointments() {
            const now = new Date();
            
            this.upcomingAppointments = this.appointments.filter(apt => {
                const aptDate = new Date(apt.date + ' ' + apt.time);
                return (apt.status === 'confirmed' || apt.status === 'scheduled') && aptDate >= now;
            }).map(apt => this.formatAppointment(apt));
            
            this.pendingAppointments = this.appointments.filter(apt => {
                return apt.status === 'pending';
            }).map(apt => this.formatAppointment(apt));
            
            this.completedAppointments = this.appointments.filter(apt => {
                return apt.status === 'completed';
            }).map(apt => this.formatAppointment(apt));
            
            this.cancelledAppointments = this.appointments.filter(apt => {
                return apt.status === 'cancelled' || apt.status === 'canceled';
            }).map(apt => this.formatAppointment(apt));
            
            // Update counts
            this.upcomingCount = this.upcomingAppointments.length;
            this.pendingCount = this.pendingAppointments.length;
            this.completedCount = this.completedAppointments.length;
            this.cancelledCount = this.cancelledAppointments.length;
        },
        
        // Format appointment for display
        formatAppointment(apt) {
            const date = new Date(apt.date);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            
            return {
                ...apt,
                month: months[date.getMonth()],
                day: date.getDate(),
                weekday: days[date.getDay()],
                isSoon: this.isWithin24Hours(apt.date, apt.time)
            };
        },
        
        // Check if appointment is within 24 hours
        isWithin24Hours(dateStr, timeStr) {
            const aptDate = new Date(dateStr + ' ' + timeStr);
            const now = new Date();
            const diff = aptDate - now;
            return diff > 0 && diff < 24 * 60 * 60 * 1000;
        },
        
        // Get status class for badge
        getStatusClass(status) {
            const classes = {
                'confirmed': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                'scheduled': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                'completed': 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                'cancelled': 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                'canceled': 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
            };
            return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';
        },
        
        // Get type icon
        getTypeIcon(type) {
            const icons = {
                'video': 'fa-video',
                'phone': 'fa-phone',
                'in-person': 'fa-hospital',
                'clinic': 'fa-hospital',
                'home': 'fa-home'
            };
            return icons[type] || 'fa-stethoscope';
        },
        
        // View appointment details
        viewDetails(appointment) {
            window.location.href = `/appointment/confirmation/${appointment.id}`;
        },
        
        // Reschedule appointment
        rescheduleAppointment(appointment) {
            this.selectedAppointment = appointment;
            this.showRescheduleModal = true;
        },
        
        // Cancel appointment
        cancelAppointment(appointment) {
            this.selectedAppointment = appointment;
            this.showCancelModal = true;
        },
        
        // Confirm cancellation
        async confirmCancel() {
            if (!this.selectedAppointment) return;
            
            try {
                const response = await fetch(`/appointment/cancel/${this.selectedAppointment.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        reason: this.cancellationReason
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showCancelModal = false;
                    this.cancellationReason = '';
                    await this.loadAppointments();
                } else {
                    alert(result.message || 'Failed to cancel appointment');
                }
            } catch (error) {
                console.error('Cancel failed:', error);
                alert('Failed to cancel appointment. Please try again.');
            }
        },
        
        // Delete appointment
        async deleteAppointment(appointment) {
            if (!confirm('Are you sure you want to delete this appointment?')) return;
            
            try {
                const response = await fetch(`/appointment/delete/${appointment.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                });

                const raw = await response.text();
                let result;
                try {
                    result = JSON.parse(raw);
                } catch {
                    console.error('Delete: non-JSON response', raw.slice(0, 200));
                    alert('Delete failed: server returned an unexpected response.');
                    return;
                }
                
                if (result.success) {
                    await this.loadAppointments();
                } else {
                    alert(result.message || 'Failed to delete appointment');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                alert('Failed to delete appointment. Please try again.');
            }
        },
        
        // Submit review
        async submitReview() {
            if (!this.selectedAppointment) return;
            
            try {
                const response = await fetch(`/appointment/${this.selectedAppointment.id}/review`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating: this.reviewRating,
                        comment: this.reviewComment
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showReviewModal = false;
                    this.reviewRating = 5;
                    this.reviewComment = '';
                    await this.loadAppointments();
                } else {
                    alert(result.message || 'Failed to submit review');
                }
            } catch (error) {
                console.error('Review failed:', error);
                alert('Failed to submit review. Please try again.');
            }
        }
    };
}

// Register patientDashboard with Alpine
Alpine.data('patientDashboard', patientDashboard);
