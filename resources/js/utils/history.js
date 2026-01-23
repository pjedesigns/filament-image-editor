/**
 * HistoryManager - Undo/Redo state management
 */

export class HistoryManager {
    constructor(maxStates = 50) {
        this.maxStates = maxStates;
        this.states = [];
        this.currentIndex = -1;
    }

    /**
     * Push a new state to the history
     */
    push(state) {
        // Remove any states after current index (branching)
        if (this.currentIndex < this.states.length - 1) {
            this.states = this.states.slice(0, this.currentIndex + 1);
        }

        // Add new state
        this.states.push(JSON.stringify(state));
        this.currentIndex++;

        // Trim if exceeding max states
        if (this.states.length > this.maxStates) {
            this.states.shift();
            this.currentIndex--;
        }
    }

    /**
     * Undo - go back one state
     */
    undo() {
        if (!this.canUndo()) return null;

        this.currentIndex--;
        return JSON.parse(this.states[this.currentIndex]);
    }

    /**
     * Redo - go forward one state
     */
    redo() {
        if (!this.canRedo()) return null;

        this.currentIndex++;
        return JSON.parse(this.states[this.currentIndex]);
    }

    /**
     * Check if undo is available
     */
    canUndo() {
        return this.currentIndex > 0;
    }

    /**
     * Check if redo is available
     */
    canRedo() {
        return this.currentIndex < this.states.length - 1;
    }

    /**
     * Get current state
     */
    getCurrentState() {
        if (this.currentIndex < 0 || this.currentIndex >= this.states.length) {
            return null;
        }
        return JSON.parse(this.states[this.currentIndex]);
    }

    /**
     * Clear all history
     */
    clear() {
        this.states = [];
        this.currentIndex = -1;
    }

    /**
     * Get history size
     */
    get size() {
        return this.states.length;
    }
}
