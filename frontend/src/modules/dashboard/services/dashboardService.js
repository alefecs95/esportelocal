import api from '@/services/api';

export default {
  getStats() {
    return api.get('/dashboard/stats');
  },
  getCalendarEvents(params) {
    return api.get('/dashboard/calendar', { params });
  }
};
