 /**
 * Doctor Dashboard JavaScript Module
 * Handles patient list, patient chart, clinical notes, and communication interfaces
 */

// Medical color palette
const medicalColors = {
    primary: '#00A790',
    primaryLight: 'rgba(0, 167, 144, 0.1)',
    secondary: '#6366f1',
    secondaryLight: 'rgba(99, 102, 241, 0.1)',
    success: '#22c55e',
    successLight: 'rgba(34, 197, 94, 0.1)',
    warning: '#f59e0b',
    warningLight: 'rgba(245, 158, 11, 0.1)',
    danger: '#ef4444',
    dangerLight: 'rgba(239, 68, 68, 0.1)',
    info: '#3b82f6',
    infoLight: 'rgba(59, 130, 246, 0.1)',
};

// Initialize Alpine components when Alpine is ready
// Alpine is imported and started in app.js, so we use the global instance
document.addEventListener('alpine:init', () => {
    
    // Patient List Component
    Alpine.data('patientList', () => ({
        // State
        searchQuery: '',
        statusFilter: 'all',
        conditionFilter: 'all',
        sortBy: 'name',
        currentPage: 1,
        itemsPerPage: 10,
        
        // Stats
        stats: {
            totalPatients: 0,
            criticalAlerts: 0,
            followUp: 0,
            todayAppointments: 0,
        },
        
        // Data
        patients: [],
        filteredPatients: [],
        
        init() {
            this.loadPatients();
            this.updateStats();
        },
        
        async loadPatients() {
            try {
                console.log('Loading consultations...');
                const response = await fetch('/health/doctor/api/consultations');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Consultations loaded:', data.length);
                
                // Map consultations to patient objects
                this.patients = data.map(consultation => ({
                    id: consultation.id,
                    patientId: consultation.patientId || consultation.consultationId || consultation.id,
                    consultationId: consultation.id,
                    name: consultation.name || 'Name unavailable',
                    age: consultation.age || '--',
                    gender: consultation.gender || 'M',
                    fileNumber: consultation.fileNumber || '--',
                    avatar: consultation.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(consultation.name || 'P')}&background=00A790&color=fff`,
                    status: consultation.status || 'stable',
                    healthScore: consultation.healthScore || 85,
                    conditions: consultation.conditions || [],
                    lastVisitDate: consultation.lastVisitDate || '--',
                    lastVisitTime: consultation.lastVisitTime || '',
                    reasonForVisit: consultation.reasonForVisit || '',
                }));
                
                this.stats.totalPatients = this.patients.length;
                this.updateFilteredPatients();
                
            } catch (error) {
                console.error('Error loading patients:', error);
                // Fallback data with proper UUIDs for patientId
                this.patients = [
                    { id: 1, patientId: '550e8400-e29b-41d4-a716-446655440001', consultationId: 1, name: 'Ahmed Ben Ali', age: 45, gender: 'M', fileNumber: 'CONS-0001', status: 'active', healthScore: 78, conditions: ['Hypertension'], lastVisitDate: '01/15/2025', reasonForVisit: 'Follow-up visit' },
                    { id: 2, patientId: '550e8400-e29b-41d4-a716-446655440002', consultationId: 2, name: 'Fatma Trabelsi', age: 32, gender: 'F', fileNumber: 'CONS-0002', status: 'follow-up', healthScore: 82, conditions: ['Type 2 diabetes'], lastVisitDate: '01/14/2025', reasonForVisit: 'Monthly check-in' },
                    { id: 3, patientId: '550e8400-e29b-41d4-a716-446655440003', consultationId: 3, name: 'Mohamed Khmiri', age: 58, gender: 'M', fileNumber: 'CONS-0003', status: 'critical', healthScore: 45, conditions: ['Heart failure'], lastVisitDate: '01/10/2025', reasonForVisit: 'Cardiac emergency' },
                    { id: 4, patientId: '550e8400-e29b-41d4-a716-446655440004', consultationId: 4, name: 'Salma Bouaziz', age: 28, gender: 'F', fileNumber: 'CONS-0004', status: 'active', healthScore: 92, conditions: [], lastVisitDate: '01/08/2025', reasonForVisit: 'General consultation' },
                    { id: 5, patientId: '550e8400-e29b-41d4-a716-446655440005', consultationId: 5, name: 'Ali Mougou', age: 65, gender: 'M', fileNumber: 'CONS-0005', status: 'follow-up', healthScore: 68, conditions: ['Asthma', 'Allergies'], lastVisitDate: '01/05/2025', reasonForVisit: 'Asthma follow-up' },
                ];
                this.stats.totalPatients = this.patients.length;
                this.updateFilteredPatients();
            }
        },
        
        updateFilteredPatients() {
            let result = [...this.patients];

            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(p =>
                    p.name.toLowerCase().includes(query) ||
                    p.fileNumber.toLowerCase().includes(query) ||
                    (p.reasonForVisit && p.reasonForVisit.toLowerCase().includes(query)) ||
                    (Array.isArray(p.conditions) && p.conditions.some(c => c.toLowerCase().includes(query)))
                );
            }

            if (this.statusFilter !== 'all') {
                result = result.filter(p => p.status === this.statusFilter);
            }

            if (this.conditionFilter !== 'all') {
                result = result.filter(p =>
                    Array.isArray(p.conditions) && p.conditions.some(c => c.toLowerCase().includes(this.conditionFilter))
                );
            }

            this.filteredPatients = result;
            this.currentPage = 1;
            this.updateStats();
        },

        updateStats() {
            this.stats.totalPatients = this.patients.length;
            this.stats.criticalAlerts = this.patients.filter(p => p.status === 'critical').length;
            this.stats.followUp = this.patients.filter(p => p.status === 'follow-up').length;
            this.stats.todayAppointments = 0;
        },

        filterPatients() {
            this.updateFilteredPatients();
        },

        sortPatients() {
            const sorted = [...this.filteredPatients];
            switch (this.sortBy) {
                case 'name':
                    sorted.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'lastVisit':
                    sorted.sort((a, b) => new Date(b.lastVisitDate) - new Date(a.lastVisitDate));
                    break;
                case 'healthScore':
                    sorted.sort((a, b) => b.healthScore - a.healthScore);
                    break;
                case 'alerts':
                    const statusPriority = { critical: 0, 'follow-up': 1, active: 2, stable: 3 };
                    sorted.sort((a, b) => statusPriority[a.status] - statusPriority[b.status]);
                    break;
            }
            this.filteredPatients = sorted;
        },
        
        get totalPages() {
            return Math.ceil(this.filteredPatients.length / this.itemsPerPage);
        },
        
        get paginatedPatients() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredPatients.slice(start, start + this.itemsPerPage);
        },
        
        setPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                critical: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                'follow-up': 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                stable: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                emergency: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
            };
            return classes[status] || classes.stable;
        },

        getStatusDotClass(status) {
            const classes = {
                active: 'bg-emerald-500',
                critical: 'bg-rose-500',
                'follow-up': 'bg-amber-500',
                stable: 'bg-blue-500',
                pending: 'bg-amber-500',
                completed: 'bg-emerald-500',
                in_progress: 'bg-blue-500',
                cancelled: 'bg-gray-500',
                emergency: 'bg-rose-500',
            };
            return classes[status] || classes.stable;
        },
        
        getStatusLabel(status) {
            const labels = {
                active: 'Active',
                critical: 'Critical',
                'follow-up': 'Follow-up required',
                stable: 'Stable',
                pending: 'Pending',
                completed: 'Completed',
                in_progress: 'In progress',
                cancelled: 'Cancelled',
                emergency: 'Emergency',
            };
            return labels[status] || status;
        },
        
        getHealthScoreColor(score) {
            if (score >= 80) return 'bg-emerald-500';
            if (score >= 60) return 'bg-amber-500';
            return 'bg-rose-500';
        },

        previousPage() {
            if (this.currentPage > 1) this.currentPage--;
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },

        openAddPatientModal() {
            alert('Feature to be implemented: add new consultation');
        },

        openMessageModal(patient) {
            window.location.href = `/health/doctor/patient/${patient.id}/communication`;
        },

        scheduleAppointment(patient) {
            alert(`Planifier un rendez-vous pour ${patient.name}`);
        },

        printPatientChart(patient) {
            window.print();
        },

        printConsultation(patient) {
            window.print();
        },

        async deleteConsultation(consultationId) {
            if (!confirm('Are you sure you want to delete this consultation?')) {
                return;
            }

            try {
                const response = await fetch(`/health/doctor/api/consultation/${consultationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success) {
                    this.patients = this.patients.filter(p => p.consultationId !== consultationId);
                    this.filterPatients();
                    this.updateStats();
                    alert('Consultation deleted successfully');
                } else {
                    alert(result.message || 'Error while deleting');
                }
            } catch (error) {
                console.error('Error while deleting:', error);
                alert('Error while deleting the consultation');
            }
        },
        
        openPatientChart(consultationId) {
            window.location.href = `/health/doctor/patient/${consultationId}/chart`;
        },
    }));

    // Patient Chart Component
    Alpine.data('patientChart', function(initialPatientData = null) {
        return {
        // State
        activeTab: 'timeline',
        timelineFilter: 'all',
        vitalsPeriod: '7d',
        consultationId: null,
        
        // Store initial data reference for debugging
        _initialPatientData: initialPatientData,
        
        // Patient Data - Use initial data if provided, otherwise defaults
        patient: initialPatientData ? JSON.parse(JSON.stringify(initialPatientData)) : {
            id: 'P001',
            name: 'Patient',
            age: '--',
            gender: 'M',
            birthDate: '--',
            fileNumber: '--',
            avatar: 'https://ui-avatars.com/api/?name=Patient&background=00A790&color=fff',
            status: 'active',
            healthScore: 85,
            conditions: [],
            lastVisitDate: '--',
            nextAppointment: null,
            bloodType: '--',
            height: 0,
            weight: 0,
            bmi: 0,
            phone: '--',
            email: '--',
            address: '--',
            emergencyContact: {
                name: '--',
                relation: '--',
                phone: '--'
            },
            allergies: [],
            medications: []
        },
        
        // Timeline Data
        timeline: [],
        
        // Vital Signs
        vitalSigns: [],
        
        // Symptoms
        symptoms: [],
        
        // Treatment / Medications
        medications: [],
        
        // Treatment (for follow-ups) - always define to prevent undefined errors
        treatment: {
            adherence: 0,
            goals: [],
            followUps: []
        },
        
        // Vital signs cards data (formatted for display)
        vitalsCards: [],
        
        // Current Consultation - for single consultation view
        currentConsultation: {
            id: null,
            plan: '',
            notes: '',
            assessment: '',
            soapNotes: {
                subjective: '',
                objective: '',
                assessment: '',
                plan: ''
            }
        },
        
        // All Consultations - for patient chart view
        allConsultations: [],
        
         init() {
            // Get consultation ID from URL - check if we're on patient-chart route
            const urlParts = window.location.pathname.split('/');
            const lastPart = urlParts[urlParts.length - 1];
            const secondLastPart = urlParts[urlParts.length - 2];
            
            // Debug: Log what we received
            console.log('Patient chart init - initialPatientData:', this._initialPatientData);
            console.log('Patient chart init - patient property:', this.patient);
            
            // Skip API call if we're on the patient-chart route (data provided via Twig)
            // OR if we're on the /doctor/patient/{id}/chart route (data also provided via Twig now)
            if (secondLastPart === 'patient-chart' || lastPart === 'chart') {
                console.log('Patient chart route detected, skipping API call. Data provided via Twig.');
                return;
            }
            
            // Initialize vitals cards with default values for non-Twig routes
            this.vitalsCards = this.formatVitalsCards();
            
            this.consultationId = lastPart !== 'chart' ? lastPart : secondLastPart;
            
            if (this.consultationId && this.consultationId !== 'chart') {
                this.loadPatientChartData();
            }
            
            this.$nextTick(() => {
                this.initCharts();
            });
        },
        
        async loadPatientChartData() {
            if (!this.consultationId) {
                console.log('No consultation ID, skipping API call');
                return;
            }
            
            try {
                console.log('Loading patient chart data:', this.consultationId);
                
                // Fetch complete patient chart data from single API endpoint
                const response = await fetch(`/health/doctor/api/patient-chart/${this.consultationId}`);
                console.log('Patient Chart API status:', response.status);
                
                if (!response.ok) {
                    console.error('Patient Chart API error:', response.statusText);
                    throw new Error('Error loading chart data');
                }
                
                const data = await response.json();
                console.log('Patient Chart data:', data);
                
                if (data.success && data.data) {
                    const normalized = this.normalizeChartData(data.data);
                    
                    // Update patient data with health score, last visit, next appointment
                    this.patient = normalized.patient;
                    
                    // Update timeline
                    this.timeline = normalized.timeline;
                    
                    // Update symptoms
                    this.symptoms = normalized.symptoms;
                    
                    // Update medications
                    this.medications = normalized.medications;
                    
                    // Update treatment data
                    this.treatment = normalized.treatment;
                    
                    // Update vital signs
                    this.vitalSigns = normalized.vitalSigns;
                    
                    // Format vital signs for display cards
                    this.vitalsCards = this.formatVitalsCards();
                    
                    console.log('Chart data loaded:', {
                        patient: this.patient,
                        timeline: this.timeline.length + ' entries',
                        symptoms: this.symptoms.length,
                        medications: this.medications.length,
                        vitals: this.vitalSigns.length
                    });
                } else {
                    console.log('No data returned, using defaults where applicable');
                }
                
            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        },
        
        normalizeChartData(raw) {
            const patient = raw.patient || {};
            const timeline = Array.isArray(raw.timeline) ? raw.timeline : [];
            const symptoms = Array.isArray(raw.symptoms) ? raw.symptoms : [];
            const medications = Array.isArray(raw.medications) ? raw.medications : [];
            const vitalSigns = Array.isArray(raw.vitalSigns) ? raw.vitalSigns : [];
            const treatment = raw.treatment || {};
            
            const normalizedPatient = {
                id: patient.id ?? 'P001',
                name: patient.name ?? patient.reason_for_visit ?? 'Patient',
                age: patient.age ?? '--',
                gender: patient.gender ?? 'M',
                birthDate: patient.birthDate ?? patient.birth_date ?? '--',
                fileNumber: patient.fileNumber ?? patient.file_number ?? '--',
                avatar: patient.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(patient.name ?? 'Patient')}&background=00A790&color=fff`,
                status: patient.status ?? 'stable',
                healthScore: patient.healthScore ?? patient.health_score ?? 85,
                conditions: Array.isArray(patient.conditions) ? patient.conditions : [],
                lastVisitDate: patient.lastVisitDate ?? patient.last_visit_date ?? '--',
                nextAppointment: patient.nextAppointment ?? patient.next_appointment ?? null,
                bloodType: patient.bloodType ?? patient.blood_type ?? '--',
                height: patient.height ?? 0,
                weight: patient.weight ?? 0,
                bmi: patient.bmi ?? 0,
                phone: patient.phone ?? '--',
                email: patient.email ?? '--',
                address: patient.address ?? '--',
                emergencyContact: patient.emergencyContact ?? patient.emergency_contact ?? { name: '--', relation: '--', phone: '--' },
                allergies: Array.isArray(patient.allergies) ? patient.allergies : [],
                medications: Array.isArray(patient.medications) ? patient.medications : [],
            };
            
            const normalizedVitals = vitalSigns.map((v) => ({
                date: v.date ?? v.date_consultation ?? 'N/A',
                time: v.time ?? v.time_consultation ?? '',
                bloodPressure: v.bloodPressure ?? v.blood_pressure ?? (v.bpSystolic && v.bpDiastolic ? `${v.bpSystolic}/${v.bpDiastolic}` : '--'),
                heartRate: v.heartRate ?? v.heart_rate ?? v.pulse ?? null,
                temperature: v.temperature ?? null,
                weight: v.weight ?? null,
                height: v.height ?? null,
                spo2: v.spo2 ?? v.oxygenSaturation ?? v.oxygen_saturation ?? null,
            }));
            
            return {
                patient: normalizedPatient,
                timeline,
                symptoms,
                medications,
                treatment: {
                    adherence: treatment.adherence ?? 0,
                    goals: Array.isArray(treatment.goals) ? treatment.goals : [],
                    followUps: Array.isArray(treatment.followUps) ? treatment.followUps : [],
                },
                vitalSigns: normalizedVitals,
            };
        },
        
        formatVitalsCards() {
            if (!this.vitalSigns || this.vitalSigns.length === 0) {
                return [
                    { type: 'bloodPressure', label: 'Blood pressure', value: '--', unit: 'mmHg', icon: 'fa-solid fa-heart-pulse', trend: 'stable', change: '0%', normalRange: '90-140/60-90', alert: false },
                    { type: 'heartRate', label: 'Heart rate', value: '--', unit: 'bpm', icon: 'fa-solid fa-heart', trend: 'stable', change: '0%', normalRange: '60-100', alert: false },
                    { type: 'temperature', label: 'Temperature', value: '--', unit: '°C', icon: 'fa-solid fa-thermometer', trend: 'stable', change: '0%', normalRange: '36.1-37.2', alert: false },
                    { type: 'spo2', label: 'SpO₂', value: '--', unit: '%', icon: 'fa-solid fa-lungs', trend: 'stable', change: '0%', normalRange: '95-100', alert: false }
                ];
            }
            
            const latest = this.vitalSigns[0];
            const previous = this.vitalSigns[1];
            
            return [
                {
                    type: 'bloodPressure',
                    label: 'Blood pressure',
                    value: latest.bloodPressure || '--',
                    unit: 'mmHg',
                    icon: 'fa-solid fa-heart-pulse',
                    trend: 'stable',
                    change: '0%',
                    normalRange: '90-140/60-90',
                    alert: false
                },
                {
                    type: 'heartRate',
                    label: 'Heart rate',
                    value: latest.heartRate || '--',
                    unit: 'bpm',
                    icon: 'fa-solid fa-heart',
                    trend: previous ? (latest.heartRate > previous.heartRate ? 'up' : latest.heartRate < previous.heartRate ? 'down' : 'stable') : 'stable',
                    change: previous ? Math.abs(latest.heartRate - previous.heartRate) + '%' : '0%',
                    normalRange: '60-100',
                    alert: latest.heartRate < 60 || latest.heartRate > 100
                },
                {
                    type: 'temperature',
                    label: 'Temperature',
                    value: latest.temperature?.toFixed(1) || '--',
                    unit: '°C',
                    icon: 'fa-solid fa-thermometer',
                    trend: previous ? (latest.temperature > previous.temperature ? 'up' : latest.temperature < previous.temperature ? 'down' : 'stable') : 'stable',
                    change: previous ? Math.abs(latest.temperature - previous.temperature).toFixed(1) + '%' : '0%',
                    normalRange: '36.1-37.2',
                    alert: latest.temperature < 36 || latest.temperature > 38
                },
                {
                    type: 'spo2',
                    label: 'SpO₂',
                    value: latest.spo2 || '--',
                    unit: '%',
                    icon: 'fa-solid fa-lungs',
                    trend: previous ? (latest.spo2 > previous.spo2 ? 'up' : latest.spo2 < previous.spo2 ? 'down' : 'stable') : 'stable',
                    change: previous ? Math.abs(latest.spo2 - previous.spo2) + '%' : '0%',
                    normalRange: '95-100',
                    alert: latest.spo2 < 95
                }
            ];
        },
        
        updateVitalsChart() {
            console.log('Updating vitals chart for period:', this.vitalsPeriod);
            this.vitalsCards = this.formatVitalsCards();
        },
        
        toggleGoal(goal) {
            goal.completed = !goal.completed;
        },
        
        initCharts() {
            // Initialize charts if needed
        },
        
        get filteredTimeline() {
            if (this.timelineFilter === 'all') return this.timeline;
            return this.timeline.filter(t => t.type === this.timelineFilter);
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                critical: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                'follow-up': 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                stable: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                emergency: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
            };
            return classes[status] || classes.active;
        },
        
        getStatusLabel(status) {
            const labels = {
                active: 'Active',
                critical: 'Critical',
                'follow-up': 'Follow-up required',
                stable: 'Stable',
                pending: 'Pending',
                completed: 'Completed',
                in_progress: 'In progress',
                cancelled: 'Cancelled',
                emergency: 'Emergency',
            };
            return labels[status] || status;
        },
        
        getHealthScoreColor(score) {
            if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
            if (score >= 60) return 'text-amber-600 dark:text-amber-400';
            return 'text-rose-600 dark:text-rose-400';
        },
        
        getBMIColor(bmi) {
            if (bmi < 18.5) return 'text-amber-600';
            if (bmi < 25) return 'text-emerald-600';
            if (bmi < 30) return 'text-amber-600';
            return 'text-rose-600';
        },
        
        getTimelineDotClass(type) {
            const classes = {
                symptom: 'bg-amber-500',
                medication: 'bg-blue-500',
                appointment: 'bg-wellcare-500',
                lab: 'bg-purple-500',
            };
            return classes[type] || 'bg-gray-500';
        },
        
        getTimelineBadgeClass(type) {
            const classes = {
                symptom: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                medication: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                appointment: 'bg-wellcare-100 text-wellcare-800 dark:bg-wellcare-900/30 dark:text-wellcare-300',
                lab: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },
        
        getSeverityColor(severity) {
            if (severity <= 2) return 'text-emerald-500';
            if (severity <= 4) return 'text-amber-500';
            return 'text-rose-500';
        },
        
        getIntensityColor(intensity) {
            if (intensity <= 3) return 'bg-emerald-500';
            if (intensity <= 6) return 'bg-amber-500';
            return 'bg-rose-500';
        },
        
        openMessageModal() {
            window.location.href = `/doctor/patient/${this.patient.id}/communication`;
        },
        
        printChart() {
            window.print();
        },
        
        openAddSymptomModal() {
            // Placeholder for add symptom modal
        },
    }});

    // Clinical Notes Component
    Alpine.data('clinicalNotes', () => ({
        // State
        selectedNote: null,
        showNewNoteModal: false,
        newDiagnosis: '',
        newLabTest: '',
        
        // Patient
        patient: {
            id: 'P001',
            name: 'Marie Dupont',
            fileNumber: '2024-001',
            avatar: 'https://ui-avatars.com/api/?name=Marie+Dupont&background=00A790&color=fff',
            status: 'active',
        },
        
        // Notes List
        notes: [
            { id: 1, type: 'SOAP', typeLabel: 'SOAP note', date: '1/15/2026', author: 'Dr. Martin', summary: 'Hypertension follow-up', isComplete: true },
            { id: 2, type: 'SOAP', typeLabel: 'SOAP note', date: '1/1/2026', author: 'Dr. Martin', summary: 'Annual check-up', isComplete: true },
            { id: 3, type: 'Prescription', typeLabel: 'Prescription', date: '12/20/2025', author: 'Dr. Martin', summary: 'Medication renewal', isComplete: true },
            { id: 4, type: 'LabResult', typeLabel: 'Lab result', date: '12/15/2025', author: 'Central Lab', summary: 'Complete blood count', isComplete: true },
            { id: 5, type: 'Progress', typeLabel: 'Progress note', date: '12/10/2025', author: 'Dr. Martin', summary: 'Symptom improvement', isComplete: false },
        ],
        
        // Templates
        templates: {
            soap: {
                subjective: 'Patient reports',
                objective: 'Clinical exam',
                assessment: 'Assessment',
                plan: 'Treatment plan'
            }
        },
        
        // Form Data
        newNote: {
            type: 'SOAP',
            subjective: '',
            objective: '',
            assessment: '',
            plan: '',
            prescriptions: [],
            labTests: []
        },
        
        // Validation
        validationErrors: {},
        
        // Computed
        get filteredNotes() {
            return this.notes;
        },
        
        get todaysNotes() {
            const today = new Date().toLocaleDateString('en-US');
            return this.notes.filter(note => note.date === today);
        },
        
        get pendingNotes() {
            return this.notes.filter(note => !note.isComplete);
        },
        
        // Methods
        selectNote(note) {
            this.selectedNote = note;
        },
        
        openNewNoteModal() {
            this.showNewNoteModal = true;
            this.resetForm();
        },
        
        closeNewNoteModal() {
            this.showNewNoteModal = false;
            this.resetForm();
        },
        
        resetForm() {
            this.newNote = {
                type: 'SOAP',
                subjective: '',
                objective: '',
                assessment: '',
                plan: '',
                prescriptions: [],
                labTests: []
            };
            this.validationErrors = {};
        },
        
        validateForm() {
            this.validationErrors = {};
            
            if (this.newNote.type === 'SOAP') {
                if (!this.newNote.subjective.trim()) {
                    this.validationErrors.subjective = 'This field is required';
                }
                if (!this.newNote.assessment.trim()) {
                    this.validationErrors.assessment = 'This field is required';
                }
            }
            
            return Object.keys(this.validationErrors).length === 0;
        },
        
        saveNote() {
            if (!this.validateForm()) {
                return;
            }
            
            const note = {
                id: Date.now(),
                type: this.newNote.type,
                typeLabel: this.getNoteTypeLabel(this.newNote.type),
                date: new Date().toLocaleDateString('en-US'),
                author: 'Dr. Martin',
                summary: this.newNote.type === 'SOAP' 
                    ? `${this.newNote.subjective.substring(0, 50)}...`
                    : this.newNote.type === 'Prescription'
                        ? this.newNote.prescriptions.map(p => p.name).join(', ')
                        : 'New entry',
                isComplete: true
            };
            
            this.notes.unshift(note);
            this.closeNewNoteModal();
            
            // Show success notification
            this.showNotification('Note saved successfully');
        },
        
        addPrescription() {
            this.newNote.prescriptions.push({
                id: Date.now(),
                name: '',
                dosage: '',
                frequency: '',
                duration: ''
            });
        },
        
        removePrescription(id) {
            this.newNote.prescriptions = this.newNote.prescriptions.filter(p => p.id !== id);
        },
        
        addLabTest() {
            this.newNote.labTests.push({
                id: Date.now(),
                name: '',
                urgency: 'routine'
            });
        },
        
        removeLabTest(id) {
            this.newNote.labTests = this.newNote.labTests.filter(t => t.id !== id);
        },
        
        getNoteTypeLabel(type) {
            const labels = {
                'SOAP': 'SOAP note',
                'Prescription': 'Prescription',
                'LabResult': 'Lab result',
                'Progress': 'Progress note',
                'Discharge': 'Discharge'
            };
            return labels[type] || type;
        },
        
        formatDate(date) {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        getStatusClass(isComplete) {
            return isComplete 
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        },
        
        showNotification(message) {
            // Simple notification - could be enhanced with Alpine toast component
            console.log('Notification:', message);
        },
        
        toggleGoal(goal) {
            goal.completed = !goal.completed;
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                critical: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                'follow-up': 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                stable: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                emergency: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
            };
            return classes[status] || classes.active;
        },
        
        getStatusLabel(status) {
            const labels = {
                active: 'Active',
                critical: 'Critical',
                'follow-up': 'Follow-up required',
                stable: 'Stable',
                pending: 'Pending',
                completed: 'Completed',
                in_progress: 'In progress',
                cancelled: 'Cancelled',
                emergency: 'Emergency',
            };
            return labels[status] || status;
        },
        
        getHealthScoreColor(score) {
            if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
            if (score >= 60) return 'text-amber-600 dark:text-amber-400';
            return 'text-rose-600 dark:text-rose-400';
        },
        
        getSeverityColor(severity) {
            if (severity <= 2) return 'text-emerald-500';
            if (severity <= 4) return 'text-amber-500';
            return 'text-rose-500';
        },
        
        printChart() {
            window.print();
        },
    }));

    // Doctor Communication Component
    Alpine.data('doctorCommunication', () => ({
        // State
        activeTab: 'messages',
        searchQuery: '',
        filterStatus: 'all',
        showNewMessageModal: false,
        selectedConversation: null,
        newMessage: '',
        messages: [],
        
        // Conversations List
        conversations: [
            {
                id: 1,
                patientName: 'Marie Dupont',
                patientAvatar: 'https://ui-avatars.com/api/?name=Marie+Dupont&background=00A790&color=fff',
                lastMessage: 'Thank you for your help, Doctor.',
                lastMessageTime: '10:30',
                unreadCount: 0,
                status: 'active',
                type: 'patient'
            },
            {
                id: 2,
                patientName: 'Central Pharmacy',
                patientAvatar: 'https://ui-avatars.com/api/?name=Central+Pharmacy&background=6366f1&color=fff',
                lastMessage: 'Prescription confirmed',
                lastMessageTime: 'Yesterday',
                unreadCount: 2,
                status: 'active',
                type: 'pharmacy'
            },
            {
                id: 3,
                patientName: 'Dr. Sophie Bernard',
                patientAvatar: 'https://ui-avatars.com/api/?name=Sophie+Bernard&background=8b5cf6&color=fff',
                lastMessage: 'Follow-up visit scheduled',
                lastMessageTime: 'Yesterday',
                unreadCount: 1,
                status: 'active',
                type: 'doctor'
            },
            {
                id: 4,
                patientName: 'Jean Martin',
                patientAvatar: 'https://ui-avatars.com/api/?name=Jean+Martin&background=10b981&color=fff',
                lastMessage: 'Questions about medications',
                lastMessageTime: '12/01/2026',
                unreadCount: 0,
                status: 'active',
                type: 'patient'
            },
            {
                id: 5,
                patientName: 'Radiology department',
                patientAvatar: 'https://ui-avatars.com/api/?name=Radiology+Dept&background=f59e0b&color=fff',
                lastMessage: 'Imaging results are ready',
                lastMessageTime: '10/01/2026',
                unreadCount: 0,
                status: 'active',
                type: 'department'
            }
        ],
        
        // Message Templates
        templates: [
            { id: 1, name: 'Appointment confirmed', content: 'Your appointment is confirmed for {date} at {time}. Please arrive 15 minutes early.' },
            { id: 2, name: 'Treatment reminder', content: 'Please remember to take your medication as prescribed. If you have any questions, contact me.' },
            { id: 3, name: 'Lab results', content: 'Your lab results are ready. Please schedule an appointment to review them.' },
            { id: 4, name: 'Emergency', content: 'In an emergency, call your local emergency number or go to the nearest emergency department.' }
        ],
        
        // Computed
        get filteredConversations() {
            let result = this.conversations;
            
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(c => 
                    c.patientName.toLowerCase().includes(query)
                );
            }
            
            if (this.filterStatus !== 'all') {
                result = result.filter(c => c.status === this.filterStatus);
            }
            
            return result;
        },
        
        get unreadCount() {
            return this.conversations.reduce((sum, c) => sum + c.unreadCount, 0);
        },
        
        // Methods
        selectConversation(conversation) {
            this.selectedConversation = conversation;
            conversation.unreadCount = 0;
            this.loadMessages(conversation);
        },
        
        loadMessages(conversation) {
            // Simulated message loading
            this.messages = [
                {
                    id: 1,
                    sender: conversation.type === 'doctor' ? 'them' : 'me',
                    content: 'Hello, I have a question about my treatment.',
                    time: '09:00',
                    status: 'read'
                },
                {
                    id: 2,
                    sender: conversation.type === 'doctor' ? 'me' : 'them',
                    content: 'Of course — how can I help?',
                    time: '09:15',
                    status: 'read'
                },
                {
                    id: 3,
                    sender: conversation.type === 'doctor' ? 'them' : 'me',
                    content: conversation.lastMessage,
                    time: conversation.lastMessageTime,
                    status: 'delivered'
                }
            ];
        },
        
        sendMessage() {
            if (!this.newMessage.trim() || !this.selectedConversation) return;
            
            const message = {
                id: Date.now(),
                sender: 'me',
                content: this.newMessage,
                time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
                status: 'sending'
            };
            
            this.messages.push(message);
            this.newMessage = '';
            
            // Simulate message sent
            setTimeout(() => {
                message.status = 'delivered';
            }, 1000);
            
            // Update conversation
            this.selectedConversation.lastMessage = message.content;
            this.selectedConversation.lastMessageTime = message.time;
        },
        
        openNewMessageModal() {
            this.showNewMessageModal = true;
        },
        
        closeNewMessageModal() {
            this.showNewMessageModal = false;
        },
        
        useTemplate(template) {
            this.newMessage = template.content;
        },
        
        getStatusDotClass(status) {
            const classes = {
                active: 'bg-emerald-500',
                away: 'bg-amber-500',
                busy: 'bg-rose-500',
                offline: 'bg-gray-400'
            };
            return classes[status] || classes.offline;
        },
        
        getTypeIcon(type) {
            const icons = {
                patient: 'fa-user',
                doctor: 'fa-user-md',
                pharmacy: 'fa-prescription-bottle',
                department: 'fa-building'
            };
            return icons[type] || 'fa-comment';
        },
        
        formatDate(date) {
            const d = new Date(date);
            const today = new Date();
            
            if (d.toDateString() === today.toDateString()) {
                return date;
            }
            
            return d.toLocaleDateString('en-US', { 
                day: '2-digit', 
                month: 'short'
            });
        },
        
        openPatientChart(patientId) {
            window.location.href = `/health/doctor/patient/${patientId}/chart`;
        },
        
        getHealthScoreColor(score) {
            if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
            if (score >= 60) return 'text-amber-600 dark:text-amber-400';
            return 'text-rose-600 dark:text-rose-400';
        },
    }));

}); // End of alpine:init listener
