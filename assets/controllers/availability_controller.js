import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };
    static targets = ['count', 'label'];

    connect() {
        if (!this.hasUrlValue) return;
        this.source = new EventSource(this.urlValue);
        this.source.onmessage = (event) => {
            const availability = JSON.parse(event.data);
            this.countTarget.textContent = availability.remainingSeats;
            this.labelTarget.textContent = availability.remainingSeats > 0 ? 'places disponibles' : 'liste d’attente';
            this.element.classList.toggle('is-full', availability.remainingSeats === 0);
        };
    }

    disconnect() {
        this.source?.close();
    }
}
