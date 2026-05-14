/**
 * Doctor Schedule Management JavaScript Module
 * Handles calendar views, appointment management, patient queue, and availability settings
 */

// Medical color palette for schedule
const scheduleColors = {
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
    newPatient: '#8b5cf6',
    followUp: '#06b6d4',
    procedure: '#f97316',
    teleconsult: '#10b981',
    emergency: '#ef4444',
};

// Appointment types with colors
const appointmentTypeColors = {
    newPatient: { bg: 'bg-purple-100', text: 'text-purple-800', border: 'border-purple-300', hex: '#8b5cf6' },
    followUp: { bg: 'bg-cyan-100', text: 'text-cyan-800', border: 'border-cyan-300', hex: '#06b6d4' },
    procedure: { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-300', hex: '#f97316' },
    teleconsult: { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-300', hex: '#10b981' },
    emergency: { bg: 'bg-red-100', text: 'text-red-800', border: 'border-red-300', hex: '#ef4444' },
    consultation: { bg: 'bg-wellcare-100', text: 'text-wellcare-800', border: 'border-wellcare-300', hex: '#00A790' },
};

document.addEventListener('alpine:init', () => {
    
    // ============================================
    // DAY VIEW COMPONENT
    // ============================================
    Alpine.data('dayViewSchedule', () => ({
        // State
        currentDate: new Date(),
        selectedAppointment: null,
        showAppointmentModal: false,
        showEmergencyModal: false,
        viewMode: 'day',
        currentHour: new Date().getHours(),
        
        // Working hours
        workingHours: {
            start: 8,
            end: 18,
            breakStart: 12,
            breakEnd: 13,
        },
        
        // Appointments data
        appointments: [],
        
        // Time slots
        timeSlots: [],
        
        init() {
            this.generateTimeSlots();
            this.loadAppointmentsFromAPI();
            this.startClock();
            this.initDragAndDrop();
        },
        
        async loadAppointmentsFromAPI() {
            try {
                const response = await fetch('/appointment/api/doctor/accepted');
                const data = await response.json();
                
                if (data.success && data.appointments) {
                    this.appointments = data.appointments.map(apt => ({
                        id: apt.id,
                        patientId: apt.patientId || 'P001',
                        patientName: apt.patientName || 'Patient',
                        type: this.mapAppointmentType(apt.consultationType),
                        time: apt.time || '09:00',
                        duration: apt.duration || 30,
                        status: apt.status || 'scheduled',
                        reason: apt.reason || '',
                        date: apt.date,
                        location: apt.mode === 'in-person' ? 'clinic' : 'telehealth',
                        notes: apt.notes || ''
                    }));
                    console.log('Loaded appointments from API:', this.appointments.length);
                }
            } catch (error) {
                console.error('Failed to load appointments:', error);
                // Fallback to simulated data
                this.loadAppointments();
            }
        },
        
        mapAppointmentType(type) {
            const typeMap = {
                'first-visit': 'newPatient',
                'follow-up': 'followUp',
                'emergency': 'emergency',
                'procedure': 'procedure'
            };
            return typeMap[type] || 'consultation';
        },
        
        startClock() {
            setInterval(() => {
                this.currentHour = new Date().getHours();
            }, 60000);
        },
        
        generateTimeSlots() {
            this.timeSlots = [];
            for (let hour = this.workingHours.start; hour < this.workingHours.end; hour++) {
                for (let min = 0; min < 60; min += 30) {
                    const time = `${hour.toString().padStart(2, '0')}:${min.toString().padStart(2, '0')}`;
                    const isBreak = hour >= this.workingHours.breakStart && hour < this.workingHours.breakEnd;
                    this.timeSlots.push({
                        time,
                        hour,
                        minute: min,
                        isBreak,
                        isPast: this.isTimePast(time),
                        isCurrentHour: hour === this.currentHour,
                    });
                }
            }
        },
        
        isTimePast(time) {
            const [hours, minutes] = time.split(':').map(Number);
            const slotTime = new Date();
            slotTime.setHours(hours, minutes, 0, 0);
            return slotTime < new Date();
        },
        
        loadAppointments() {
            // Simulated appointment data
            this.appointments = [
                {
                    id: 'APT001',
                    patientId: 'P001',
                    patientName: 'Marie Dupont',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Marie+Dupont&background=00A790&color=fff',
                    type: 'newPatient',
                    time: '08:30',
                    duration: 45,
                    status: 'checked-in',
                    reason: 'Première consultation - Hypertension',
                    priority: 'normal',
                    notes: 'Patient référé par Dr. Smith pour suivi tensionnel',
                    insurance: 'CNAM',
                    room: 'Bureau 1',
                },
                {
                    id: 'APT002',
                    patientId: 'P002',
                    patientName: 'Jean Martin',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Jean+Martin&background=ef4444&color=fff',
                    type: 'followUp',
                    time: '09:30',
                    duration: 30,
                    status: 'waiting',
                    reason: 'Suivi diabète - Bilan HbA1c',
                    priority: 'normal',
                    notes: '',
                    insurance: 'CNAM',
                    room: 'Bureau 1',
                    waitTime: 15,
                },
                {
                    id: 'APT003',
                    patientId: 'P003',
                    patientName: 'Sophie Bernard',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Sophie+Bernard&background=6366f1&color=fff',
                    type: 'procedure',
                    time: '10:15',
                    duration: 60,
                    status: 'scheduled',
                    reason: 'Pose de stent',
                    priority: 'high',
                    notes: 'Procédure programmée avec équipe chirurgicale',
                    insurance: 'CNAM',
                    room: 'Salle 2',
                },
                {
                    id: 'APT004',
                    patientId: 'P004',
                    patientName: 'Pierre Leroy',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Pierre+Leroy&background=f59e0b&color=fff',
                    type: 'teleconsult',
                    time: '11:30',
                    duration: 20,
                    status: 'ready',
                    reason: 'Téléconsultation de suivi',
                    priority: 'normal',
                    notes: 'Conférence Zoom préparée',
                    insurance: 'CNAM',
                    room: 'Virtuel',
                    meetingLink: 'https://zoom.us/j/123456789',
                },
                {
                    id: 'APT005',
                    patientId: 'P005',
                    patientName: 'Isabelle Petit',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Isabelle+Petit&background=22c55e&color=fff',
                    type: 'followUp',
                    time: '14:00',
                    duration: 30,
                    status: 'scheduled',
                    reason: 'Suivi postnatal',
                    priority: 'normal',
                    notes: '',
                    insurance: 'CNAM',
                    room: 'Bureau 2',
                },
            ];
        },
        
        getAppointmentsForSlot(time) {
            return this.appointments.filter(apt => apt.time === time);
        },
        
        getAppointmentClass(appointment) {
            const baseClass = 'appointment-card';
            const typeClass = appointmentTypeColors[appointment.type]?.bg || appointmentTypeColors.consultation.bg;
            const priorityClass = appointment.priority === 'high' || appointment.type === 'emergency' 
                ? 'ring-2 ring-red-500 ring-offset-2' 
                : '';
            const statusClass = this.getStatusClass(appointment.status);
            return `${baseClass} ${typeClass} ${priorityClass} ${statusClass}`;
        },
        
        getStatusClass(status) {
            const classes = {
                'checked-in': 'border-l-4 border-l-emerald-500',
                'waiting': 'border-l-4 border-l-amber-500',
                'ready': 'border-l-4 border-l-blue-500',
                'in-progress': 'border-l-4 border-l-wellcare-500',
                'completed': 'opacity-60',
                'cancelled': 'line-through opacity-50',
                'no-show': 'bg-gray-200 dark:bg-gray-700',
            };
            return classes[status] || '';
        },
        
        getAppointmentTypeLabel(type) {
            const labels = {
                newPatient: 'Nouveau patient',
                followUp: 'Suivi',
                procedure: 'Procédure',
                teleconsult: 'Téléconsultation',
                emergency: 'Urgence',
                consultation: 'Consultation',
            };
            return labels[type] || type;
        },
        
        getWaitTimeColor(waitTime) {
            if (waitTime < 15) return 'text-emerald-600';
            if (waitTime < 30) return 'text-amber-600';
            return 'text-red-600';
        },
        
        openAppointment(appointment) {
            this.selectedAppointment = appointment;
            this.showAppointmentModal = true;
        },
        
        closeAppointmentModal() {
            this.showAppointmentModal = false;
            this.selectedAppointment = null;
        },
        
        startConsultation(appointment) {
            appointment.status = 'in-progress';
            window.location.href = `/consultation/${appointment.id}`;
        },
        
        checkInPatient(appointment) {
            appointment.status = 'checked-in';
            appointment.checkInTime = new Date().toLocaleTimeString();
        },
        
        markNoShow(appointment) {
            appointment.status = 'no-show';
        },
        
        cancelAppointment(appointment) {
            appointment.status = 'cancelled';
        },
        
        rescheduleAppointment(appointment) {
            alert(`Replanifier le rendez-vous de ${appointment.patientName}`);
        },
        
        openEmergencyModal() {
            this.showEmergencyModal = true;
        },
        
        closeEmergencyModal() {
            this.showEmergencyModal = false;
        },
        
        addEmergencySlot() {
            alert('Créer un créneau d\'urgence');
        },
        
        // Drag and drop functionality
        initDragAndDrop() {
            const cards = document.querySelectorAll('.appointment-card');
            const slots = document.querySelectorAll('.time-slot');
            
            cards.forEach(card => {
                card.draggable = true;
                card.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', card.dataset.appointmentId);
                    card.classList.add('opacity-50');
                });
                card.addEventListener('dragend', () => {
                    card.classList.remove('opacity-50');
                });
            });
            
            slots.forEach(slot => {
                slot.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    slot.classList.add('bg-wellcare-50', 'dark:bg-wellcare-900/20');
                });
                slot.addEventListener('dragleave', () => {
                    slot.classList.remove('bg-wellcare-50', 'dark:bg-wellcare-900/20');
                });
                slot.addEventListener('drop', (e) => {
                    e.preventDefault();
                    slot.classList.remove('bg-wellcare-50', 'dark:bg-wellcare-900/20');
                    const appointmentId = e.dataTransfer.getData('text/plain');
                    const newTime = slot.dataset.time;
                    this.moveAppointment(appointmentId, newTime);
                });
            });
        },
        
        moveAppointment(appointmentId, newTime) {
            const appointment = this.appointments.find(apt => apt.id === appointmentId);
            if (appointment) {
                appointment.time = newTime;
            }
        },
        
        // Navigation
        previousDay() {
            this.currentDate.setDate(this.currentDate.getDate() - 1);
            this.currentDate = new Date(this.currentDate);
            this.loadAppointments();
        },
        
        nextDay() {
            this.currentDate.setDate(this.currentDate.getDate() + 1);
            this.currentDate = new Date(this.currentDate);
            this.loadAppointments();
        },
        
        goToToday() {
            this.currentDate = new Date();
            this.loadAppointments();
        },
        
        getFormattedDate() {
            return this.currentDate.toLocaleDateString('fr-FR', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        },
        
        // Export functions
        exportToPDF() {
            alert('Exporter le planning en PDF');
        },
        
        exportToICal() {
            alert('Exporter vers iCal');
        },
        
        printSchedule() {
            window.print();
        },
    }));

    // ============================================
    // WEEK VIEW COMPONENT
    // ============================================
    Alpine.data('weekViewSchedule', () => ({
        // State
        currentWeekStart: this.getWeekStart(new Date()),
        selectedDate: null,
        selectedAppointment: null,
        showAppointmentModal: false,
        viewMode: 'week',
        
        // Locations
        locations: [
            { id: 'clinic', name: 'Cabinet Principal', color: '#00A790' },
            { id: 'hospital', name: 'Hôpital Central', color: '#6366f1' },
            { id: 'telehealth', name: 'Téléconsultation', color: '#10b981' },
        ],
        selectedLocation: 'all',
        
        // Week days
        weekDays: [],
        
        // Appointments data
        appointments: [],
        
        init() {
            this.generateWeekDays();
            this.loadAppointmentsFromAPI();
        },
        
        async loadAppointmentsFromAPI() {
            try {
                const response = await fetch('/appointment/api/doctor/accepted');
                const data = await response.json();
                
                if (data.success && data.appointments) {
                    this.appointments = data.appointments.map(apt => ({
                        id: apt.id,
                        patientId: apt.patientId || 'P001',
                        patientName: apt.patientName || 'Patient',
                        type: this.mapAppointmentType(apt.consultationType),
                        date: apt.date,
                        time: apt.time || '09:00',
                        duration: apt.duration || 30,
                        status: apt.status || 'scheduled',
                        reason: apt.reason || '',
                        location: apt.mode === 'in-person' ? 'clinic' : 'telehealth',
                        notes: apt.notes || ''
                    }));
                    console.log('Loaded appointments from API:', this.appointments.length);
                }
            } catch (error) {
                console.error('Failed to load appointments:', error);
                // Fallback to simulated data
                this.loadAppointments();
            }
        },
        
        mapAppointmentType(type) {
            const typeMap = {
                'first-visit': 'newPatient',
                'follow-up': 'followUp',
                'emergency': 'emergency',
                'procedure': 'procedure'
            };
            return typeMap[type] || 'consultation';
        },
        
        getWeekStart(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(d.setDate(diff));
        },
        
        generateWeekDays() {
            this.weekDays = [];
            for (let i = 0; i < 7; i++) {
                const date = new Date(this.currentWeekStart);
                date.setDate(date.getDate() + i);
                this.weekDays.push({
                    date,
                    dayName: date.toLocaleDateString('fr-FR', { weekday: 'short' }),
                    dayNumber: date.getDate(),
                    month: date.toLocaleDateString('fr-FR', { month: 'short' }),
                    isToday: this.isToday(date),
                    isWeekend: date.getDay() === 0 || date.getDay() === 6,
                });
            }
        },
        
        isToday(date) {
            const today = new Date();
            return date.toDateString() === today.toDateString();
        },
        
        loadAppointments() {
            // Simulated week appointments - used as fallback
            this.appointments = [
                // Monday
                { id: 'W001', patientId: 'P001', patientName: 'Marie Dupont', type: 'newPatient', date: this.weekDays[0]?.date, time: '09:00', duration: 45, location: 'clinic', status: 'scheduled' },
                { id: 'W002', patientId: 'P002', patientName: 'Jean Martin', type: 'followUp', date: this.weekDays[0]?.date, time: '10:00', duration: 30, location: 'clinic', status: 'scheduled' },
                { id: 'W003', patientId: 'P003', patientName: 'Sophie Bernard', type: 'procedure', date: this.weekDays[0]?.date, time: '14:00', duration: 60, location: 'hospital', status: 'scheduled' },
                
                // Tuesday
                { id: 'W004', patientId: 'P004', patientName: 'Pierre Leroy', type: 'teleconsult', date: this.weekDays[1]?.date, time: '09:30', duration: 20, location: 'telehealth', status: 'scheduled' },
                { id: 'W005', patientId: 'P005', patientName: 'Isabelle Petit', type: 'followUp', date: this.weekDays[1]?.date, time: '11:00', duration: 30, location: 'clinic', status: 'scheduled' },
                
                // Wednesday
                { id: 'W006', patientId: 'P006', patientName: 'Michel Durand', type: 'consultation', date: this.weekDays[2]?.date, time: '08:30', duration: 30, location: 'clinic', status: 'scheduled' },
                { id: 'W007', patientId: 'P007', patientName: 'Anne Moreau', type: 'newPatient', date: this.weekDays[2]?.date, time: '10:00', duration: 45, location: 'clinic', status: 'scheduled' },
                
                // Thursday
                { id: 'W008', patientId: 'P008', patientName: 'Paul Roux', type: 'procedure', date: this.weekDays[3]?.date, time: '09:00', duration: 90, location: 'hospital', status: 'scheduled' },
                { id: 'W009', patientId: 'P009', patientName: 'Catherine Blanc', type: 'teleconsult', date: this.weekDays[3]?.date, time: '14:00', duration: 30, location: 'telehealth', status: 'scheduled' },
                
                // Friday
                { id: 'W010', patientId: 'P010', patientName: 'Thomas Girard', type: 'followUp', date: this.weekDays[4]?.date, time: '09:00', duration: 30, location: 'clinic', status: 'scheduled' },
                { id: 'W011', patientId: 'P011', patientName: 'Sophie Martin', type: 'newPatient', date: this.weekDays[4]?.date, time: '10:00', duration: 45, location: 'clinic', status: 'scheduled' },
            ];
        },
        
        async loadAppointmentsFromAPI() {
            try {
                const response = await fetch('/appointment/api/doctor/accepted');
                const data = await response.json();
                
                if (data.success && data.appointments) {
                    this.appointments = data.appointments.map(apt => ({
                        id: apt.id,
                        patientId: apt.patientId || 'P001',
                        patientName: apt.patientName || 'Patient',
                        type: this.mapAppointmentType(apt.consultationType),
                        date: apt.date,
                        time: apt.time || '09:00',
                        duration: apt.duration || 30,
                        status: apt.status || 'scheduled',
                        reason: apt.reason || '',
                        location: apt.mode === 'in-person' ? 'clinic' : 'telehealth',
                        notes: apt.notes || ''
                    }));
                    console.log('Loaded appointments from API:', this.appointments.length);
                }
            } catch (error) {
                console.error('Failed to load appointments:', error);
                // Fallback to simulated data
                this.loadAppointments();
            }
        },
        
        mapAppointmentType(type) {
            const typeMap = {
                'first-visit': 'newPatient',
                'follow-up': 'followUp',
                'emergency': 'emergency',
                'procedure': 'procedure'
            };
            return typeMap[type] || 'consultation';
        },
        
        getAppointmentsForDay(day) {
            if (!day || !day.date) return [];
            return this.appointments.filter(apt => {
                const aptDate = new Date(apt.date);
                return aptDate.toDateString() === day.date.toDateString();
            });
        },
        
        getAppointmentHeight(duration) {
            return duration; // 1px per minute
        },
        
        getAppointmentTop(time) {
            const [hours, minutes] = time.split(':').map(Number);
            const startHour = 7; // 7 AM
            return ((hours - startHour) * 60 + minutes);
        },
        
        getLocationColor(locationId) {
            const location = this.locations.find(l => l.id === locationId);
            return location?.color || '#00A790';
        },
        
        getLocationName(locationId) {
            const location = this.locations.find(l => l.id === locationId);
            return location?.name || locationId;
        },
        
        openAppointment(appointment) {
            this.selectedAppointment = appointment;
            this.showAppointmentModal = true;
        },
        
        closeAppointmentModal() {
            this.showAppointmentModal = false;
            this.selectedAppointment = null;
        },
        
        previousWeek() {
            this.currentWeekStart.setDate(this.currentWeekStart.getDate() - 7);
            this.generateWeekDays();
            this.loadAppointments();
        },
        
        nextWeek() {
            this.currentWeekStart.setDate(this.currentWeekStart.getDate() + 7);
            this.generateWeekDays();
            this.loadAppointments();
        },
        
        goToCurrentWeek() {
            this.currentWeekStart = this.getWeekStart(new Date());
            this.generateWeekDays();
            this.loadAppointments();
        },
        
        getWeekRange() {
            const start = this.weekDays[0];
            const end = this.weekDays[6];
            if (!start || !end) return '';
            return `${start.dayNumber} ${start.month} - ${end.dayNumber} ${end.month} ${end.date.getFullYear()}`;
        },
        
        getStatistics() {
            const stats = {
                total: this.appointments.length,
                newPatients: this.appointments.filter(a => a.type === 'newPatient').length,
                procedures: this.appointments.filter(a => a.type === 'procedure').length,
                teleconsults: this.appointments.filter(a => a.type === 'teleconsult').length,
                hospital: this.appointments.filter(a => a.location === 'hospital').length,
            };
            return stats;
        },
    }));

    // ============================================
    // MONTH VIEW COMPONENT
    // ============================================
    Alpine.data('monthViewSchedule', () => ({
        // State
        currentMonth: new Date(),
        selectedDate: null,
        selectedAppointment: null,
        showAppointmentModal: false,
        
        // Calendar data
        calendarDays: [],
        
        // Appointments data
        appointments: [],
        
        // Leave/vacation
        leaveDays: [],
        
        init() {
            this.generateCalendarDays();
            this.loadAppointments();
            this.loadLeaveDays();
        },
        
        generateCalendarDays() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            const startPadding = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
            
            this.calendarDays = [];
            
            // Previous month padding
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startPadding - 1; i >= 0; i--) {
                const date = new Date(year, month - 1, prevMonthLastDay - i);
                this.calendarDays.push({
                    date,
                    day: date.getDate(),
                    isCurrentMonth: false,
                    isToday: this.isToday(date),
                    isWeekend: date.getDay() === 0 || date.getDay() === 6,
                    appointments: this.getAppointmentsForDate(date),
                });
            }
            
            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                this.calendarDays.push({
                    date,
                    day: i,
                    isCurrentMonth: true,
                    isToday: this.isToday(date),
                    isWeekend: date.getDay() === 0 || date.getDay() === 6,
                    appointments: this.getAppointmentsForDate(date),
                });
            }
            
            // Next month padding
            const remainingDays = 42 - this.calendarDays.length;
            for (let i = 1; i <= remainingDays; i++) {
                const date = new Date(year, month + 1, i);
                this.calendarDays.push({
                    date,
                    day: i,
                    isCurrentMonth: false,
                    isToday: this.isToday(date),
                    isWeekend: date.getDay() === 0 || date.getDay() === 6,
                    appointments: this.getAppointmentsForDate(date),
                });
            }
        },
        
        isToday(date) {
            const today = new Date();
            return date.toDateString() === today.toDateString();
        },
        
        getAppointmentsForDate(date) {
            return this.appointments.filter(apt => {
                const aptDate = new Date(apt.date);
                return aptDate.toDateString() === date.toDateString();
            });
        },
        
        loadAppointments() {
            // Generate appointments for the month
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            
            this.appointments = [
                // First week
                { id: 'M001', patientId: 'P001', patientName: 'Marie Dupont', type: 'newPatient', date: new Date(year, month, 2), time: '09:00', status: 'scheduled' },
                { id: 'M002', patientId: 'P002', patientName: 'Jean Martin', type: 'followUp', date: new Date(year, month, 2), time: '10:00', status: 'scheduled' },
                { id: 'M003', patientId: 'P003', patientName: 'Sophie Bernard', type: 'procedure', date: new Date(year, month, 3), time: '14:00', status: 'scheduled' },
                
                // Second week
                { id: 'M004', patientId: 'P004', patientName: 'Pierre Leroy', type: 'teleconsult', date: new Date(year, month, 8), time: '09:30', status: 'scheduled' },
                { id: 'M005', patientId: 'P005', patientName: 'Isabelle Petit', type: 'followUp', date: new Date(year, month, 9), time: '11:00', status: 'scheduled' },
                
                // Third week
                { id: 'M006', patientId: 'P006', patientName: 'Michel Durand', type: 'consultation', date: new Date(year, month, 15), time: '08:30', status: 'scheduled' },
                { id: 'M007', patientId: 'P007', patientName: 'Anne Moreau', type: 'newPatient', date: new Date(year, month, 16), time: '10:00', status: 'scheduled' },
                
                // Fourth week
                { id: 'M008', patientId: 'P008', patientName: 'Paul Roux', type: 'procedure', date: new Date(year, month, 22), time: '09:00', status: 'scheduled' },
                { id: 'M009', patientId: 'P009', patientName: 'Catherine Blanc', type: 'teleconsult', date: new Date(year, month, 23), time: '14:00', status: 'scheduled' },
            ];
        },
        
        loadLeaveDays() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            
            this.leaveDays = [
                { id: 'L001', type: 'vacation', startDate: new Date(year, month, 20), endDate: new Date(year, month, 25), reason: 'Congés annuels' },
                { id: 'L002', type: 'conference', startDate: new Date(year, month, 28), endDate: new Date(year, month, 29), reason: 'Conférence médicale' },
            ];
        },
        
        isOnLeave(date) {
            return this.leaveDays.some(leave => {
                return date >= new Date(leave.startDate) && date <= new Date(leave.endDate);
            });
        },
        
        getLeaveType(type) {
            const types = {
                vacation: 'Congés',
                conference: 'Conférence',
                sick: 'Maladie',
                training: 'Formation',
                emergency: 'Urgence personnelle',
            };
            return types[type] || type;
        },
        
        getAppointmentCountForDay(day) {
            return day.appointments?.length || 0;
        },
        
        getAppointmentIndicators(day) {
            const count = this.getAppointmentCountForDay(day);
            return {
                total: count,
                newPatient: day.appointments?.filter(a => a.type === 'newPatient').length || 0,
                procedure: day.appointments?.filter(a => a.type === 'procedure').length || 0,
                teleconsult: day.appointments?.filter(a => a.type === 'teleconsult').length || 0,
            };
        },
        
        openDayDetail(day) {
            this.selectedDate = day;
            // Show day detail modal or navigate to day view
        },
        
        previousMonth() {
            this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
            this.generateCalendarDays();
            this.loadAppointments();
            this.loadLeaveDays();
        },
        
        nextMonth() {
            this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
            this.generateCalendarDays();
            this.loadAppointments();
            this.loadLeaveDays();
        },
        
        goToCurrentMonth() {
            this.currentMonth = new Date();
            this.generateCalendarDays();
            this.loadAppointments();
            this.loadLeaveDays();
        },
        
        getMonthYearLabel() {
            return this.currentMonth.toLocaleDateString('fr-FR', { 
                year: 'numeric', 
                month: 'long' 
            });
        },
        
        getMonthlyStatistics() {
            const stats = {
                totalAppointments: this.appointments.length,
                newPatients: this.appointments.filter(a => a.type === 'newPatient').length,
                procedures: this.appointments.filter(a => a.type === 'procedure').length,
                teleconsults: this.appointments.filter(a => a.type === 'teleconsult').length,
                noShows: 3, // Simulated
                cancellations: 5, // Simulated
                workingDays: this.calendarDays.filter(d => d.isCurrentMonth && !d.isWeekend).length,
                leaveDays: this.leaveDays.reduce((acc, leave) => {
                    const days = Math.ceil((new Date(leave.endDate) - new Date(leave.startDate)) / (1000 * 60 * 60 * 24)) + 1;
                    return acc + days;
                }, 0),
            };
            return stats;
        },
    }));

    // ============================================
    // PATIENT QUEUE COMPONENT
    // ============================================
    Alpine.data('patientQueue', () => ({
        // State
        queue: [],
        checkedInPatients: [],
        waitingPatients: [],
        inConsultation: [],
        completedToday: [],
        
        // Real-time update
        lastUpdate: new Date(),
        
        // Statistics
        stats: {
            totalWaiting: 0,
            avgWaitTime: 0,
            totalConsultations: 0,
            remainingToday: 0,
        },
        
        init() {
            this.loadQueue();
            this.startRealTimeUpdates();
        },
        
        loadQueue() {
            // Queue data
            this.queue = [
                {
                    id: 'Q001',
                    patientId: 'P001',
                    patientName: 'Marie Dupont',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Marie+Dupont&background=00A790&color=fff',
                    appointmentTime: '08:30',
                    appointmentType: 'newPatient',
                    status: 'waiting',
                    checkInTime: '08:15',
                    waitTime: 30,
                    priority: 'normal',
                    reason: 'Première consultation - Hypertension',
                    notes: 'Patient référé par Dr. Smith',
                    room: 'Bureau 1',
                    phone: '+33 6 12 34 56 78',
                },
                {
                    id: 'Q002',
                    patientId: 'P002',
                    patientName: 'Jean Martin',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Jean+Martin&background=ef4444&color=fff',
                    appointmentTime: '09:00',
                    appointmentType: 'followUp',
                    status: 'waiting',
                    checkInTime: '08:50',
                    waitTime: 15,
                    priority: 'normal',
                    reason: 'Suivi diabète - Bilan HbA1c',
                    notes: '',
                    room: 'Bureau 1',
                    phone: '+33 6 23 45 67 89',
                },
                {
                    id: 'Q003',
                    patientId: 'P003',
                    patientName: 'Sophie Bernard',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Sophie+Bernard&background=6366f1&color=fff',
                    appointmentTime: '09:30',
                    appointmentType: 'procedure',
                    status: 'ready',
                    checkInTime: '09:15',
                    waitTime: 0,
                    priority: 'high',
                    reason: 'Procédure programmée',
                    notes: 'Procédure avec équipe chirurgicale',
                    room: 'Salle 2',
                    phone: '+33 6 34 56 78 90',
                },
                {
                    id: 'Q004',
                    patientId: 'P004',
                    patientName: 'Pierre Leroy',
                    patientAvatar: 'https://ui-avatars.com/api/?name=Pierre+Leroy&background=f59e0b&color=fff',
                    appointmentTime: '10:00',
                    appointmentType: 'teleconsult',
                    status: 'ready',
                    checkInTime: '09:45',
                    waitTime: 0,
                    priority: 'normal',
                    reason: 'Téléconsultation de suivi',
                    notes: 'Conférence Zoom préparée',
                    room: 'Virtuel',
                    meetingLink: 'https://zoom.us/j/123456789',
                    phone: '+33 6 45 67 89 01',
                },
            ];
            
            this.updateStats();
        },
        
        startRealTimeUpdates() {
            setInterval(() => {
                this.updateWaitTimes();
                this.lastUpdate = new Date();
            }, 30000); // Update every 30 seconds
        },
        
        updateWaitTimes() {
            this.queue.forEach(patient => {
                if (patient.status === 'waiting') {
                    patient.waitTime = Math.floor((new Date() - new Date(`2024-01-01 ${patient.checkInTime}`)) / 60000);
                }
            });
        },
        
        updateStats() {
            this.stats.totalWaiting = this.queue.filter(p => p.status === 'waiting').length;
            const waitingPatients = this.queue.filter(p => p.status === 'waiting');
            this.stats.avgWaitTime = waitingPatients.length > 0
                ? Math.floor(waitingPatients.reduce((sum, p) => sum + p.waitTime, 0) / waitingPatients.length)
                : 0;
            this.stats.totalConsultations = this.completedToday.length;
            this.stats.remainingToday = this.queue.length - this.stats.totalConsultations;
        },
        
        getPriorityClass(priority) {
            const classes = {
                emergency: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                normal: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            };
            return classes[priority] || classes.normal;
        },
        
        getPriorityLabel(priority) {
            const labels = {
                emergency: 'Urgence',
                high: 'Prioritaire',
                normal: 'Normal',
                low: 'Faible',
            };
            return labels[priority] || priority;
        },
        
        getWaitTimeColor(waitTime) {
            if (waitTime < 15) return 'text-emerald-600';
            if (waitTime < 30) return 'text-amber-600';
            if (waitTime < 60) return 'text-orange-600';
            return 'text-red-600';
        },
        
        getAppointmentTypeLabel(type) {
            const labels = {
                newPatient: 'Nouveau patient',
                followUp: 'Suivi',
                procedure: 'Procédure',
                teleconsult: 'Téléconsultation',
                emergency: 'Urgence',
                consultation: 'Consultation',
            };
            return labels[type] || type;
        },
        
        startConsultation(patient) {
            patient.status = 'in-progress';
            window.location.href = `/consultation/${patient.id}`;
        },
        
        checkInPatient(patient) {
            patient.status = 'checked-in';
            patient.checkInTime = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            this.updateStats();
        },
        
        markReady(patient) {
            patient.status = 'ready';
            this.updateStats();
        },
        
        markCompleted(patient) {
            patient.status = 'completed';
            this.completedToday.push({
                ...patient,
                completionTime: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
            });
            this.queue = this.queue.filter(p => p.id !== patient.id);
            this.updateStats();
        },
        
        markNoShow(patient) {
            patient.status = 'no-show';
            this.queue = this.queue.filter(p => p.id !== patient.id);
            this.updateStats();
        },
        
        reschedulePatient(patient) {
            alert(`Replanifier le rendez-vous de ${patient.patientName}`);
        },
        
        sendReminder(patient) {
            alert(`Envoyer un rappel à ${patient.patientName} (${patient.phone})`);
        },
        
        togglePriority(patient) {
            const priorities = ['low', 'normal', 'high', 'emergency'];
            const currentIndex = priorities.indexOf(patient.priority);
            patient.priority = priorities[(currentIndex + 1) % priorities.length];
        },
        
        addToQueue(patientData) {
            const newPatient = {
                id: `Q${Date.now()}`,
                ...patientData,
                status: 'waiting',
                checkInTime: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
                waitTime: 0,
            };
            this.queue.push(newPatient);
            this.updateStats();
        },
        
        sortByWaitTime() {
            this.queue.sort((a, b) => b.waitTime - a.waitTime);
        },
        
        sortByPriority() {
            const priorityOrder = { emergency: 0, high: 1, normal: 2, low: 3 };
            this.queue.sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);
        },
        
        sortByAppointmentTime() {
            this.queue.sort((a, b) => a.appointmentTime.localeCompare(b.appointmentTime));
        },
        
        getQueueLength() {
            return this.queue.length;
        },
        
        getWaitingCount() {
            return this.queue.filter(p => p.status === 'waiting').length;
        },
        
        getReadyCount() {
            return this.queue.filter(p => p.status === 'ready').length;
        },
        
        getInProgressCount() {
            return this.queue.filter(p => p.status === 'in-progress').length;
        },
    }));

    // ============================================
    // AVAILABILITY SETTINGS COMPONENT
    // ============================================
    Alpine.data('availabilitySettings', () => ({
        // State
        activeTab: 'working-hours',
        showAddSlotModal: false,
        showLeaveModal: false,
        showLocationModal: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        
        // Working hours
        workingHours: {
            monday: { enabled: true, start: '08:00', end: '18:00', breaks: [{ start: '12:00', end: '13:00' }] },
            tuesday: { enabled: true, start: '08:00', end: '18:00', breaks: [{ start: '12:00', end: '13:00' }] },
            wednesday: { enabled: true, start: '08:00', end: '18:00', breaks: [{ start: '12:00', end: '13:00' }] },
            thursday: { enabled: true, start: '08:00', end: '18:00', breaks: [{ start: '12:00', end: '13:00' }] },
            friday: { enabled: true, start: '08:00', end: '18:00', breaks: [{ start: '12:00', end: '13:00' }] },
            saturday: { enabled: false, start: '09:00', end: '13:00', breaks: [] },
            sunday: { enabled: false, start: '00:00', end: '00:00', breaks: [] },
        },
        
        // Weekly schedule for new template
        weeklySchedule: [
            { name: 'Monday', key: 'monday', isActive: true, startTime: '08:00', endTime: '18:00', location: 'clinic', slots: [] },
            { name: 'Tuesday', key: 'tuesday', isActive: true, startTime: '08:00', endTime: '18:00', location: 'clinic', slots: [] },
            { name: 'Wednesday', key: 'wednesday', isActive: true, startTime: '08:00', endTime: '18:00', location: 'clinic', slots: [] },
            { name: 'Thursday', key: 'thursday', isActive: true, startTime: '08:00', endTime: '18:00', location: 'clinic', slots: [] },
            { name: 'Friday', key: 'friday', isActive: true, startTime: '08:00', endTime: '18:00', location: 'clinic', slots: [] },
            { name: 'Saturday', key: 'saturday', isActive: false, startTime: '09:00', endTime: '13:00', location: 'clinic', slots: [] },
            { name: 'Sunday', key: 'sunday', isActive: false, startTime: '09:00', endTime: '13:00', location: 'clinic', slots: [] },
        ],
        
        // Settings
        settings: {
            defaultAppointmentDuration: '30',
            appointmentGap: '5',
            lunchBreakStart: '12:00',
            lunchBreakEnd: '13:00',
            emergencySlotsPerDay: 2,
            allowDoubleBooking: false,
            autoConfirmAppointments: false,
        },
        
        // Time slots configuration
        slotDuration: 30, // minutes
        bufferTime: 5, // minutes between appointments
        emergencyBuffer: 15, // minutes for emergencies
        
        // Leave and time off
        leaveRequests: [],
        plannedLeaves: [],
        
        // New leave form
        newLeave: {
            type: 'vacation',
            title: '',
            startDate: '',
            endDate: '',
            reason: '',
        },
        
        // Locations
        locations: [
            { id: 'loc1', name: 'Main office', type: 'clinic', address: '123 Health Street, Tunis', phone: '+216 71 123 456', isActive: true },
            { id: 'loc2', name: 'Central Hospital', type: 'hospital', address: '456 Hospital Avenue, Tunis', phone: '+216 71 987 654', isActive: true },
        ],
        
        // New location form
        newLocation: {
            name: '',
            type: 'clinic',
            address: '',
            phone: '',
        },
        
        // Special blocks
        specialBlocks: [],
        
        // Substitution
        substituteDoctor: null,
        autoAssignSubstitute: false,
        availableSubstitutes: [],
        showSubstituteModal: false,
        selectedSubstituteUuid: null,
        
        // Stats
        weeklyStats: {
            appointments: 42,
            patientsSeen: 38,
        },
        
        init() {
            this.loadLeaveRequests();
            this.loadSpecialBlocks();
            this.loadSettings();
        },
        
        loadSettings() {
            // Load settings from server
            fetch('/health/doctor/availability/settings/load')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.settings) {
                            this.settings = { ...this.settings, ...data.settings };
                        }
                        if (data.weeklySchedule) {
                            this.weeklySchedule = data.weeklySchedule;
                        }
                        if (data.locations) {
                            this.locations = data.locations;
                        }
                        if (data.leaves) {
                            this.plannedLeaves = data.leaves;
                        }
                    }
                })
                .catch(error => console.log('Could not load settings:', error));
        },
        
        loadLeaveRequests() {
            this.plannedLeaves = [
                { id: 'LR001', type: 'vacation', title: 'Summer break', startDate: '2026-02-20', endDate: '2026-02-25', days: 5, reason: 'Annual leave', status: 'approved' },
                { id: 'LR002', type: 'conference', title: 'Medical conference', startDate: '2026-03-15', endDate: '2026-03-17', days: 2, reason: 'Cardiology conference', status: 'pending' },
            ];
        },
        
        loadSpecialBlocks() {
            this.specialBlocks = [];
        },
        
        // Computed properties
        get totalWeeklyHours() {
            return this.weeklySchedule
                .filter(day => day.isActive)
                .reduce((total, day) => {
                    const [startH, startM] = day.startTime.split(':').map(Number);
                    const [endH, endM] = day.endTime.split(':').map(Number);
                    return total + (endH + endM/60) - (startH + startM/60);
                }, 0);
        },
        
        get workDaysRatio() {
            const activeDays = this.weeklySchedule.filter(day => day.isActive).length;
            return activeDays / 7;
        },
        
        // Methods
        getDayName(day) {
            const names = {
                monday: 'Monday',
                tuesday: 'Tuesday',
                wednesday: 'Wednesday',
                thursday: 'Thursday',
                friday: 'Friday',
                saturday: 'Saturday',
                sunday: 'Sunday',
            };
            return names[day] || day;
        },
        
        toggleDay(day) {
            this.workingHours[day].enabled = !this.workingHours[day].enabled;
        },
        
        updateDayTime(day, type, value) {
            this.workingHours[day][type] = value;
        },
        
        addBreak(day) {
            this.workingHours[day].breaks.push({ start: '12:30', end: '13:30' });
        },
        
        removeBreak(day, index) {
            this.workingHours[day].breaks.splice(index, 1);
        },
        
        addSlot(day) {
            day.slots.push({ start: day.startTime, end: day.endTime });
        },
        
        removeSlot(day, index) {
            day.slots.splice(index, 1);
        },
        
        // Save settings
        async saveSettings() {
            try {
                const response = await fetch('/health/doctor/availability/settings/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        weeklySchedule: this.weeklySchedule,
                        settings: this.settings,
                        locations: this.locations,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    this.showToastMessage('Settings saved successfully', 'success');
                } else {
                    this.showToastMessage('Error while saving', 'error');
                }
            } catch (error) {
                this.showToastMessage('Connection error', 'error');
            }
        },
        
        // Location methods
        addLocation() {
            this.newLocation = { name: '', type: 'clinic', address: '', phone: '' };
            this.showLocationModal = true;
        },
        
        confirmAddLocation() {
            if (!this.newLocation.name || !this.newLocation.address) {
                this.showToastMessage('Please fill in required fields', 'error');
                return;
            }
            
            const location = {
                id: 'loc' + Date.now(),
                name: this.newLocation.name,
                type: this.newLocation.type,
                address: this.newLocation.address,
                phone: this.newLocation.phone,
                isActive: true,
            };
            
            this.locations.push(location);
            this.showLocationModal = false;
            this.showToastMessage('Location added successfully', 'success');
            
            // Save to server
            this.saveLocationToServer(location);
        },
        
        async saveLocationToServer(location) {
            try {
                await fetch('/health/doctor/availability/location/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(location),
                });
            } catch (error) {
                console.error('Error saving location:', error);
            }
        },
        
        // Leave methods
        openLeaveRequest() {
            this.newLeave = {
                type: 'vacation',
                title: '',
                startDate: '',
                endDate: '',
                reason: '',
            };
            this.showLeaveModal = true;
        },
        
        confirmLeaveRequest() {
            if (!this.newLeave.title || !this.newLeave.startDate || !this.newLeave.endDate) {
                this.showToastMessage('Please fill in required fields', 'error');
                return;
            }
            
            const start = new Date(this.newLeave.startDate);
            const end = new Date(this.newLeave.endDate);
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            
            const leave = {
                id: 'LR' + Date.now(),
                type: this.newLeave.type,
                title: this.newLeave.title,
                startDate: this.newLeave.startDate,
                endDate: this.newLeave.endDate,
                days: days,
                reason: this.newLeave.reason,
                status: 'pending',
            };
            
            this.plannedLeaves.push(leave);
            this.showLeaveModal = false;
            this.showToastMessage('Leave request submitted', 'success');
            
            // Save to server and mark planning as unavailable
            this.saveLeaveToServer(leave);
        },
        
        async saveLeaveToServer(leave) {
            try {
                const response = await fetch('/health/doctor/availability/leave/request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(leave),
                });
                const data = await response.json();
                if (data.success) {
                    // The server will mark the planning as unavailable for these dates
                    console.log('Leave request saved and planning updated');
                }
            } catch (error) {
                console.error('Error saving leave:', error);
            }
        },
        
        cancelLeave(leave) {
            if (confirm('Cancel this leave request?')) {
                this.plannedLeaves = this.plannedLeaves.filter(l => l.id !== leave.id);
                this.cancelLeaveOnServer(leave.id);
            }
        },
        
        async cancelLeaveOnServer(leaveId) {
            try {
                await fetch(`/health/doctor/availability/leave/${leaveId}/cancel`, {
                    method: 'POST',
                });
            } catch (error) {
                console.error('Error cancelling leave:', error);
            }
        },
        
        // Substitution
        selectSubstitute() {
            // Load available doctors from database and open modal
            fetch('/health/doctor/availability/substitutes')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.substitutes) {
                        this.availableSubstitutes = data.substitutes;
                        this.showSubstituteModal = true;
                    } else {
                        this.showToastMessage('Error loading physicians', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading substitutes:', error);
                    this.showToastMessage('Connection error', 'error');
                });
        },
        
        confirmSubstitute() {
            if (!this.selectedSubstituteUuid) {
                this.showToastMessage('Please select a physician', 'error');
                return;
            }
            
            const selected = this.availableSubstitutes.find(s => s.uuid === this.selectedSubstituteUuid);
            if (selected) {
                this.substituteDoctor = {
                    uuid: selected.uuid,
                    name: 'Dr. ' + selected.firstName + ' ' + selected.lastName,
                    specialty: selected.specialite || 'General practice'
                };
                this.showSubstituteModal = false;
                this.selectedSubstituteUuid = null;
                this.showToastMessage('Locum physician selected');
            }
        },
        
        removeSubstitute() {
            this.substituteDoctor = null;
        },
        
        // Toast notification
        showToastMessage(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => {
                this.showToast = false;
            }, 3000);
        },
        
        getLeaveTypeLabel(type) {
            const labels = {
                vacation: 'Vacation',
                conference: 'Conference',
                training: 'Training',
                sick: 'Sick leave',
                emergency: 'Personal emergency',
                personal: 'Personal',
                other: 'Other',
            };
            return labels[type] || type;
        },
        
        getLeaveStatusLabel(status) {
            const labels = {
                pending: 'Pending',
                approved: 'Approved',
                rejected: 'Rejected',
                cancelled: 'Cancelled',
            };
            return labels[status] || status;
        },
        
        getLeaveStatusClass(status) {
            const classes = {
                pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            };
            return classes[status] || classes.pending;
        },
        
        addSpecialBlock(blockData) {
            const newBlock = {
                id: `SB${Date.now()}`,
                ...blockData,
            };
            this.specialBlocks.push(newBlock);
            this.showAddSlotModal = false;
        },
        
        removeSpecialBlock(blockId) {
            this.specialBlocks = this.specialBlocks.filter(b => b.id !== blockId);
        },
        
        exportSchedule() {
            alert('Export schedule to PDF / iCal');
        },
        
        importSchedule() {
            alert('Import a schedule');
        },
        
        resetToDefaults() {
            if (confirm('Reset all settings to defaults?')) {
                this.settings = {
                    defaultAppointmentDuration: '30',
                    appointmentGap: '5',
                    lunchBreakStart: '12:00',
                    lunchBreakEnd: '13:00',
                    emergencySlotsPerDay: 2,
                    allowDoubleBooking: false,
                    autoConfirmAppointments: false,
                };
                this.showToastMessage('Paramètres réinitialisés', 'success');
            }
        },
        
        duplicateWeek() {
            alert('Dupliquer la semaine');
        },
        
        applyToAllWeeks() {
            alert('Appliquer à toutes les semaines');
        },
    }));

    // ============================================
    // SCHEDULE CONTROLLER (Main entry point)
    // ============================================
    Alpine.data('scheduleController', () => ({
        // State
        currentView: 'day',
        sidebarOpen: false,
        
        // Notifications
        notifications: [],
        
        init() {
            // Initialize based on current view
        },
        
        switchView(view) {
            this.currentView = view;
        },
        
        addNotification(message, type = 'info') {
            const notification = {
                id: Date.now(),
                message,
                type,
                timestamp: new Date(),
            };
            this.notifications.push(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                this.removeNotification(notification.id);
            }, 5000);
        },
        
        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        },
    }));
});

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Format time for display
 */
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    return `${hours}:${minutes}`;
}

/**
 * Format duration for display
 */
function formatDuration(minutes) {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
}

/**
 * Calculate time difference in minutes
 */
function getTimeDifference(time1, time2) {
    const [h1, m1] = time1.split(':').map(Number);
    const [h2, m2] = time2.split(':').map(Number);
    return (h2 * 60 + m2) - (h1 * 60 + m1);
}

/**
 * Add minutes to a time string
 */
function addMinutesToTime(time, minutes) {
    const [hours, mins] = time.split(':').map(Number);
    const totalMins = hours * 60 + mins + minutes;
    const newHours = Math.floor(totalMins / 60) % 24;
    const newMins = totalMins % 60;
    return `${newHours.toString().padStart(2, '0')}:${newMins.toString().padStart(2, '0')}`;
}

/**
 * Check if a time slot is available
 */
function isTimeSlotAvailable(time, appointments, duration) {
    const endTime = addMinutesToTime(time, duration);
    return !appointments.some(apt => {
        const aptEnd = addMinutesToTime(apt.time, apt.duration);
        return (time >= apt.time && time < aptEnd) || (endTime > apt.time && endTime <= aptEnd);
    });
}

/**
 * Generate time slots for a given range
 */
function generateTimeSlots(startHour, endHour, interval = 30) {
    const slots = [];
    for (let hour = startHour; hour < endHour; hour++) {
        for (let min = 0; min < 60; min += interval) {
            const time = `${hour.toString().padStart(2, '0')}:${min.toString().padStart(2, '0')}`;
            slots.push(time);
        }
    }
    return slots;
}

/**
 * Format date for display
 */
function formatDate(date, locale = 'fr-FR') {
    return new Date(date).toLocaleDateString(locale, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

/**
 * Format date for input fields
 */
function formatDateForInput(date) {
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * Check if a date is today
 */
function isToday(date) {
    const today = new Date();
    const checkDate = new Date(date);
    return checkDate.toDateString() === today.toDateString();
}

/**
 * Check if a date is in the past
 */
function isPast(date) {
    return new Date(date) < new Date();
}

/**
 * Get week start date
 */
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

/**
 * Get month start and end dates
 */
function getMonthRange(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    return {
        start: new Date(year, month, 1),
        end: new Date(year, month + 1, 0),
    };
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + N: New appointment
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        // Open new appointment modal
    }
    
    // Ctrl/Cmd + E: Export schedule
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        // Export schedule
    }
    
    // Ctrl/Cmd + P: Print schedule
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    
    // Escape: Close modals
    if (e.key === 'Escape') {
        // Close any open modals
    }
});

// Global doctorSchedule function for templates that use x-data="doctorSchedule"
function doctorSchedule() {
    return {
        // State
        currentWeekStart: getWeekStart(new Date()),
        selectedDate: null,
        selectedAppointment: null,
        showAppointmentModal: false,
        viewMode: 'week',
        pendingCount: 0,
        
        // Locations
        locations: [
            { id: 'clinic', name: 'Cabinet Principal', color: '#00A790' },
            { id: 'hospital', name: 'Hôpital Central', color: '#6366f1' },
            { id: 'telehealth', name: 'Téléconsultation', color: '#10b981' },
        ],
        selectedLocation: 'all',
        
        // Week days
        weekDays: [],
        hours: [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
        
        // Appointments data
        appointments: [],
        
        init() {
            this.generateWeekDays();
            this.loadAppointmentsFromAPI();
            this.loadPendingCount();
        },
        
        generateWeekDays() {
            this.weekDays = [];
            for (let i = 0; i < 7; i++) {
                const date = new Date(this.currentWeekStart);
                date.setDate(date.getDate() + i);
                this.weekDays.push({
                    date: date.toISOString().split('T')[0],
                    dateObj: date,
                    dayName: date.toLocaleDateString('fr-FR', { weekday: 'short' }),
                    dayNumber: date.getDate(),
                    month: date.toLocaleDateString('fr-FR', { month: 'short' }),
                    isToday: date.toDateString() === new Date().toDateString(),
                    isWeekend: date.getDay() === 0 || date.getDay() === 6,
                });
            }
        },
        
        getWeekStart(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(d.setDate(diff));
        },
        
        async loadAppointmentsFromAPI() {
            try {
                const response = await fetch('/appointment/api/doctor/accepted');
                const data = await response.json();
                
                if (data.success && data.appointments) {
                    this.appointments = data.appointments.map(apt => ({
                        id: apt.id,
                        patientId: apt.patientId || 'P001',
                        patientName: apt.patientName || 'Patient',
                        type: this.mapAppointmentType(apt.consultationType),
                        date: apt.date,
                        time: apt.time || '09:00',
                        duration: apt.duration || 30,
                        status: apt.status || 'scheduled',
                        reason: apt.reason || '',
                        location: apt.mode === 'in-person' ? 'clinic' : 'telehealth',
                        notes: apt.notes || ''
                    }));
                    console.log('Loaded appointments from API:', this.appointments.length);
                }
            } catch (error) {
                console.error('Failed to load appointments:', error);
            }
        },
        
        async loadPendingCount() {
            try {
                const response = await fetch('/appointment/api/doctor/pending');
                const data = await response.json();
                if (data.success) {
                    this.pendingCount = data.count;
                }
            } catch (error) {
                console.error('Failed to load pending count:', error);
            }
        },
        
        mapAppointmentType(type) {
            const typeMap = {
                'first-visit': 'newPatient',
                'follow-up': 'followUp',
                'emergency': 'emergency',
                'procedure': 'procedure'
            };
            return typeMap[type] || 'consultation';
        },
        
        getAppointmentsForDay(date) {
            if (!date) return [];
            return this.appointments.filter(apt => apt.date === date);
        },
        
        get weekRange() {
            if (this.weekDays.length === 0) return '';
            const start = this.weekDays[0];
            const end = this.weekDays[6];
            const startStr = start.dayNumber + ' ' + start.month;
            const endStr = end.dayNumber + ' ' + end.month;
            return `${startStr} - ${endStr}`;
        },
        
        previousWeek() {
            this.currentWeekStart.setDate(this.currentWeekStart.getDate() - 7);
            this.generateWeekDays();
            this.loadAppointmentsFromAPI();
        },
        
        nextWeek() {
            this.currentWeekStart.setDate(this.currentWeekStart.getDate() + 7);
            this.generateWeekDays();
            this.loadAppointmentsFromAPI();
        },
        
        goToCurrentWeek() {
            this.currentWeekStart = this.getWeekStart(new Date());
            this.generateWeekDays();
            this.loadAppointmentsFromAPI();
        },
        
        openAppointmentDetails(appointment) {
            this.selectedAppointment = appointment;
            this.showAppointmentModal = true;
        },
    };
}

// Make doctorSchedule available globally
window.doctorSchedule = doctorSchedule;
