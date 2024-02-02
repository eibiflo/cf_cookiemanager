/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import {AbstractSortableSelectItems} from "@typo3/backend/form-engine/element/abstract-sortable-select-items.js";
import DocumentService from "@typo3/core/document-service.js";
import FormEngine from "@typo3/backend/form-engine.js";
import SelectBoxFilter from "@codingfreaks/cf-cookiemanager/FormEngine/Element/Extra/SelectBoxFilter.js";
import RegularEvent from "@typo3/core/event/regular-event.js";

export default class SelectMultipleSideBySideElement extends AbstractSortableSelectItems {
    constructor(e, t) {
        super();
        this.selectedOptionsElement = null;
        this.availableOptionsElement = null;
        DocumentService.ready().then(l => {
            this.selectedOptionsElement = l.getElementById(e);
            this.availableOptionsElement = l.getElementById(t);
            this.registerEventHandler();
        });
    }

    registerEventHandler() {
        this.registerSortableEventHandler(this.selectedOptionsElement);
        this.availableOptionsElement.addEventListener("click", e => {
            const t = e.currentTarget;
            const l = t.dataset.relatedfieldname;
            if (l) {
                const e = t.dataset.exclusivevalues;
                const n = t.querySelectorAll("option:checked");
                if (n.length > 0) {
                    n.forEach(t => {
                        FormEngine.setSelectOptionFromExternalSource(l, t.value, t.textContent, t.getAttribute("title"), e, t);
                    });
                }
            }
        });
        new SelectBoxFilter(this.availableOptionsElement);
    }
}