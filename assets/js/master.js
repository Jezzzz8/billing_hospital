import { mountAdminMasterPage } from './master/admin-rpc.js';

const MASTER_PAGES = {
    'gender':           { resource: 'genders',            singular: 'gender',         hasActive: false },
    'role':             { resource: 'roles',              singular: 'role',           hasActive: false },
    'department':       { resource: 'departments',        singular: 'department',     hasActive: true  },

    'room-status':      { resource: 'room-statuses',      singular: 'status',         hasActive: false },
    'admission-status': { resource: 'admission-statuses', singular: 'status',         hasActive: false },
    'billing-status':   { resource: 'billing-statuses',   singular: 'status',         hasActive: false },
    'payment-type':     { resource: 'payment-types',      singular: 'paymentType',    hasActive: false },
    'specialization':   { resource: 'specializations',    singular: 'specialization', hasActive: false },
    'diagnosis':        { resource: 'diagnoses',          singular: 'diagnosis',      hasActive: true  },

    'charge-category':  { resource: 'charge-categories',  singular: 'category',       hasActive: false },
    'charge-item':      { resource: 'charge-items',       singular: 'item',           hasActive: true  },

    'building':         { resource: 'buildings',          singular: 'building',       hasActive: false },
    'floor-level':      { resource: 'floor-levels',       singular: 'floorLevel',     hasActive: false },
    'room-type':        { resource: 'room-types',         singular: 'roomType',       hasActive: true  },

    'doctors':          { resource: 'doctors',            singular: 'doctor',         hasActive: true  },
};

async function mountCurrentMasterPage(event) {
    const page = event.detail.page || document.body.dataset.page || '';

    if (page === 'room') {
        try {
            const baseUrl = document.body.dataset.baseUrl || '';
            const mod = await import(baseUrl + '/assets/js/master/room.js');
            if (typeof mod.initRoomPage === 'function') {
                await mod.initRoomPage(event);
            }
        } catch (error) {
            console.error('[master.js] Could not init room module:', error);
        }
        return;
    }

    const cfg = MASTER_PAGES[page];
    if (!cfg) return;
    mountAdminMasterPage(cfg);
}

document.addEventListener('spa:page-loaded', mountCurrentMasterPage);