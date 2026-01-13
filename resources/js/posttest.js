// Post-Test form helper (Alpine x-data)
// Defined in bundled JS (not inline in Blade) so it works reliably with Livewire wire:navigate.

window.posttestForm = function posttestForm(wire, requiredIds, totalQuestions) {
    return {
        lw: wire,
        answers: {},
        errors: {},
        submitted: false,
        submitting: false,
        requiredIds: Array.isArray(requiredIds) ? requiredIds : [],
        totalQuestions: Number.isFinite(+totalQuestions) ? +totalQuestions : 0,

        get answeredCount() {
            return Object.keys(this.answers || {}).length;
        },

        progressPercent() {
            return this.totalQuestions
                ? (this.answeredCount / this.totalQuestions) * 100
                : 0;
        },

        init() {
            // Intentionally empty: no autosave/restore.
        },

        validate() {
            this.errors = {};
            (this.requiredIds || []).forEach((id) => {
                if (!this.answers[id]) this.errors[id] = "Harus dipilih.";
            });
            return Object.keys(this.errors).length === 0;
        },

        resetForm() {
            if (this.$refs.formEl) this.$refs.formEl.reset();
            this.answers = {};
            this.errors = {};
            this.submitted = false;
        },

        async submit() {
            if (!this.validate()) return;
            this.submitting = true;
            this.submitted = true;
            try {
                await this.lw.submitPosttest(this.answers);
            } finally {
                this.submitting = false;
            }
        },
    };
};
