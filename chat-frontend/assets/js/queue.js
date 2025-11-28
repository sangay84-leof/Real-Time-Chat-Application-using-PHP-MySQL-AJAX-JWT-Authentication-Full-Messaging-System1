// queue.js – Simple Circular Queue implementation

export class CircularQueue {
  /**
   * Creates a new CircularQueue.
   * @param {number} capacity Maximum number of items the queue can hold.
   */
  constructor(capacity) {
    if (capacity <= 0) {
      throw new Error('Capacity must be a positive integer');
    }
    this.capacity = capacity;
    this.buffer = new Array(capacity);
    this.head = 0; // points to the oldest element
    this.tail = 0; // points to the next insertion index
    this.size = 0;
  }

  /** Add an item to the queue, overwriting the oldest if full. */
  enqueue(item) {
    this.buffer[this.tail] = item;
    this.tail = (this.tail + 1) % this.capacity;
    if (this.size < this.capacity) {
      this.size++;
    } else {
      // Overwrite: move head forward
      this.head = (this.head + 1) % this.capacity;
    }
  }

  /** Remove and return the oldest item. Returns undefined if empty. */
  dequeue() {
    if (this.size === 0) return undefined;
    const item = this.buffer[this.head];
    this.buffer[this.head] = undefined; // help GC
    this.head = (this.head + 1) % this.capacity;
    this.size--;
    return item;
  }

  /** Return the oldest item without removing it. */
  peek() {
    if (this.size === 0) return undefined;
    return 
      this.buffer[this.head];
  }

  /** Return an array of items in FIFO order. */
  toArray() {
    const result = [];
    for (let i = 0; i < this.size; i++) {
      const index = (this.head + i) % this.capacity;
      result.push(this.buffer[index]);
    }
    return result;
  }

  /** Current number of stored items. */
  get length() {
    return this.size;
  }

  /**
   * Delete an item at a specific logical index (0-based from oldest to newest).
   * @param {number} index The logical index of the item to delete (0 = oldest)
   * @returns {boolean} True if deletion was successful, false if index is out of bounds
   */
  deleteAt(index) {
    if (index < 0 || index >= this.size) {
      return false; // Invalid index
    }

    // Convert logical index to physical buffer index
    const physicalIndex = (this.head + index) % this.capacity;

    // Shift all elements after the deleted element forward
    for (let i = index; i < this.size - 1; i++) {
      const currentPhysical = (this.head + i) % this.capacity;
      const nextPhysical = (this.head + i + 1) % this.capacity;
      this.buffer[currentPhysical] = this.buffer[nextPhysical];
    }

    // Clear the last element and adjust tail
    const lastPhysical = (this.head + this.size - 1) % this.capacity;
    this.buffer[lastPhysical] = undefined;
    this.tail = lastPhysical;
    this.size--;

    return true;
  }
}
