import "./bootstrap";
import sortable from "./sortable";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "./flipbook"; // initialize flipbook viewer globally
import "./modulePage"; // module page scripts (video gate, yt api, accordion)

Alpine.directive("sortable", (el, { expression }, { evaluateLater }) => {
    let getCallback = evaluateLater(expression);

    sortable(el, (order) => {
        getCallback((callback) => {
            if (typeof callback === "function") {
                callback(order);
            }
        });
    });
});
