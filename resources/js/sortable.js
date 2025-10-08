import Sortable from "sortablejs";

window.Sortable = Sortable;

export default function sortable(el, callback) {
    Sortable.create(el, {
        animation: 150,
        onEnd: (evt) => {
            let order = Array.from(el.children).map(
                (child) => child.dataset.id
            );
            callback(order);
        },
    });
}
