define(["require", "exports", "TYPO3/CMS/Core/Event/RegularEvent"], (function (e, t, l) {
    "use strict";
    var i;
    !function (e) {
        e.fieldContainerSelector = ".t3js-formengine-field-group", e.filterTextFieldSelector = ".t3js-formengine-multiselect-filter-textfield", e.filterSelectFieldSelector = ".t3js-formengine-multiselect-filter-dropdown"
    }(i || (i = {}));

    class n {
        constructor(e) {
            this.selectElement = null, this.filterText = "", this.availableOptions = null, this.selectElement = e, this.initializeEvents()
        }

        static toggleOptGroup(e,filterText) {
            const t = e.parentElement;
            if(filterText === " "){
                e.removeAttribute("hidden");
            }
            t instanceof HTMLOptGroupElement && (0 === t.querySelectorAll("option:not([hidden]):not([disabled]):not(.hidden)").length ? t.hidden = !0 : (t.hidden = !1, t.disabled = !1, t.classList.remove("hidden")))
        }

        initializeEvents() {
            const e = this.selectElement.closest(".form-wizards-element");
            null !== e && (new l("input", e => {
                this.filter(e.target.value,true)
            }).delegateTo(e, i.filterTextFieldSelector), new l("change", e => {
                this.filter(e.target.value,false)
            }).delegateTo(e, i.filterSelectFieldSelector))

            if(typeof document.querySelector('[data-formengine-input-name*="identifier"]').value !== "undefined"){
                let currentCategory = document.querySelector('[data-formengine-input-name*="identifier"]').value;
                if(typeof currentCategory !== "undefined"){
                    e.querySelector(".form-select.form-select-sm.t3js-formengine-multiselect-filter-dropdown").value = currentCategory;
                    this.filter(currentCategory,false)
                }
            }
        }

        filter(e,freetext) {
            this.filterText = e, null === this.availableOptions && (this.availableOptions = this.selectElement.querySelectorAll("option"));
            const t = new RegExp(e, "i");
            if(freetext === true){
                this.availableOptions.forEach(l => {
                    l.hidden = e.length > 0 && null === l.getAttribute("title").match(t), n.toggleOptGroup(l,this.filterText)
                })
            }else{
                this.availableOptions.forEach(l => {
                    l.hidden = e.length > 0 && null === l.getAttribute("data-category").match(t), n.toggleOptGroup(l,this.filterText)
                })
            }
        }
    }

    return n
}));