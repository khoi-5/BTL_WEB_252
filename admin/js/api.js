/**
 * BookStore Admin API Client
 * Provides all API calls for the admin dashboard.
 * Usage: AdminAPI.contacts.list(), AdminAPI.faqs.create({...}), etc.
 */
const AdminAPI = (function () {
    const BASE = 'http://localhost:8000/api';

    // ---- helpers ----
    function get(url) {
        return fetch(BASE + url).then(r => r.json());
    }
    function post(url, body) {
        return fetch(BASE + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }).then(r => r.json());
    }
    function put(url, body) {
        return fetch(BASE + url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }).then(r => r.json());
    }
    function del(url) {
        return fetch(BASE + url, { method: 'DELETE' }).then(r => r.json());
    }
    function postForm(url, formData) {
        return fetch(BASE + url, { method: 'POST', body: formData }).then(r => r.json());
    }

    return {
        // ---- Contacts ----
        contacts: {
            list: function (q, page, limit, status) {
                var params = new URLSearchParams();
                if (q) params.set('q', q);
                if (page) params.set('page', page);
                if (limit) params.set('limit', limit);
                if (status) params.set('status', status);
                return get('/admin/contacts?' + params.toString());
            },
            detail: function (id) { return get('/admin/contacts/detail?id=' + id); },
            updateStatus: function (id, status, adminId) {
                return put('/admin/contacts/status?id=' + id, { status: status, admin_id: adminId });
            },
            remove: function (id) { return del('/admin/contacts/delete?id=' + id); },
        },

        // ---- FAQs ----
        faqs: {
            list: function (q, page, limit) {
                var params = new URLSearchParams();
                if (q) params.set('q', q);
                if (page) params.set('page', page);
                if (limit) params.set('limit', limit);
                return get('/admin/faqs?' + params.toString());
            },
            detail: function (id) { return get('/admin/faqs/detail?id=' + id); },
            create: function (data) { return post('/admin/faqs', data); },
            update: function (id, data) { return put('/admin/faqs/update?id=' + id, data); },
            remove: function (id) { return del('/admin/faqs/delete?id=' + id); },
        },

        // ---- Site Settings ----
        settings: {
            list: function () { return get('/admin/settings'); },
            grouped: function () { return get('/admin/settings/grouped'); },
            update: function (settings, adminId) {
                return put('/admin/settings/update', { settings: settings, admin_id: adminId });
            },
        },

        // ---- Categories ----
        categories: {
            listPublic: function () { return get('/categories'); },
            tree: function () { return get('/categories/tree'); },
            create: function (name, parentId) {
                return post('/admin/categories', { category_name: name, parent_category_id: parentId || null });
            },
            update: function (id, name, parentId) {
                return put('/admin/categories/update?id=' + id, { category_name: name, parent_category_id: parentId || null });
            },
            remove: function (id) { return del('/admin/categories/delete?id=' + id); },
        },

        // ---- Flash Sales ----
        flashSales: {
            list: function () { return get('/admin/flash-sales'); },
            detail: function (id) { return get('/admin/flash-sales/detail?id=' + id); },
            create: function (formData) { return postForm('/admin/flash-sales', formData); },
            update: function (id, formData) { return postForm('/admin/flash-sales/update?id=' + id, formData); },
            remove: function (id) { return del('/admin/flash-sales/delete?id=' + id); },
        },

        // ---- Sections ----
        sections: {
            list: function () { return get('/admin/sections'); },
            detail: function (id) { return get('/admin/sections/detail?id=' + id); },
            create: function (formData) { return postForm('/admin/sections', formData); },
            update: function (id, formData) { return postForm('/admin/sections/update?id=' + id, formData); },
            remove: function (id) { return del('/admin/sections/delete?id=' + id); },
        },

        // ---- Customers ----
        customers: {
            list: function (q, page, limit) {
                var params = new URLSearchParams();
                if (q) params.set('q', q);
                if (page) params.set('page', page);
                if (limit) params.set('limit', limit);
                return get('/admin/customers?' + params.toString());
            },
            updateStatus: function (id, status, adminId) {
                return put('/admin/customers/status?id=' + id, { status: status, admin_id: adminId });
            },
            resetPassword: function (customerId, adminId) {
                return put('/admin/customers/reset-password', { customer_id: customerId, admin_id: adminId });
            },
        },

        // ---- Orders ----
        orders: {
            list: function (q, page, limit) {
                var params = new URLSearchParams();
                if (q) params.set('q', q);
                if (page) params.set('page', page);
                if (limit) params.set('limit', limit);
                return get('/orders?' + params.toString());
            },
            detail: function (id) { return get('/orders/detail?id=' + id); },
            moveToShipping: function (id, adminId) {
                return put('/orders/move-to-shipping?id=' + id, { admin_id: adminId });
            },
        },

        // ---- Products ----
        products: {
            list: function (q, page, limit) {
                var params = new URLSearchParams();
                if (q) params.set('q', q);
                if (page) params.set('page', page);
                if (limit) params.set('limit', limit);
                return get('/products?' + params.toString());
            },
            detail: function (id) { return get('/products/detail?id=' + id); },
            create: function (data) { return post('/products', data); },
            update: function (id, data) { return put('/products/update?id=' + id, data); },
            remove: function (id) { return del('/products/delete?id=' + id); },
        },
    };
})();
