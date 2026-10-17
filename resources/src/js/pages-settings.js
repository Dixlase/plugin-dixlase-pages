/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Settings screen: the minimum role to publish. The range slider moves an
 * index over the role values, and the hidden publish_min_role input sends
 * the role value at that index.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('pagesPublishRole', () => ({
        publishIndex: 0,
        roleValues: [],

        init() {
            this.publishIndex = Number(this.$el.dataset.publishIndex) || 0;
            this.roleValues = JSON.parse(this.$el.dataset.roleValues || '[]');
        },

        get publishValue() {
            return this.roleValues[this.publishIndex] || this.roleValues[0];
        },
    }));
});
