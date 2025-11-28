// API client for backend communication
const API_BASE_URL = "http://localhost:8080/api";

class API {
  /**
   * Make API request
   */
  static async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const config = {
      credentials: "include", // Include cookies for session
      headers: {
        ...options.headers,
      },
      ...options,
    };

    // Add JSON content type for non-FormData requests
    if (options.body && !(options.body instanceof FormData)) {
      config.headers["Content-Type"] = "application/json";
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Request failed");
      }

      return data;
    } catch (error) {
      console.error("API Error:", error);
      throw error;
    }
  }

  /**
   * Authentication APIs
   */
  static async register(username, email, password) {
    return this.request("/auth/register.php", {
      method: "POST",
      body: JSON.stringify({ username, email, password }),
    });
  }

  static async login(username, password) {
    return this.request("/auth/login.php", {
      method: "POST",
      body: JSON.stringify({ username, password }),
    });
  }

  static async logout() {
    return this.request("/auth/logout.php", {
      method: "POST",
    });
  }

  static async getCurrentUser() {
    return this.request("/auth/me.php");
  }

  /**
   * Message APIs
   */
  static async getMessages() {
    return this.request("/messages/get.php");
  }

  static async sendMessage(text) {
    return this.request("/messages/send.php", {
      method: "POST",
      body: JSON.stringify({ text }),
    });
  }

  static async uploadFile(file) {
    const formData = new FormData();
    formData.append("file", file);

    return this.request("/messages/upload.php", {
      method: "POST",
      body: formData,
    });
  }

  static async deleteMessage(id) {
    return this.request(`/messages/delete.php?id=${id}`, {
      method: "DELETE",
    });
  }

  static async pollMessages(lastId = 0) {
    return this.request(`/messages/poll.php?lastId=${lastId}`);
  }
}

/**
 * Long polling manager
 */
class PollingManager {
  constructor(onNewMessages) {
    this.onNewMessages = onNewMessages;
    this.lastMessageId = 0;
    this.isPolling = false;
  }

  start() {
    this.isPolling = true;
    this.poll();
  }

  stop() {
    this.isPolling = false;
  }

  async poll() {
    if (!this.isPolling) return;

    try {
      const response = await API.pollMessages(this.lastMessageId);

      if (response.data.messages && response.data.messages.length > 0) {
        // Update last message ID
        const messages = response.data.messages;
        this.lastMessageId = Math.max(...messages.map((m) => m.id));

        // Notify callback
        this.onNewMessages(messages);
      }
    } catch (error) {
      console.error("Polling error:", error);
    }

    // Continue polling
    if (this.isPolling) {
      setTimeout(() => this.poll(), 1000);
    }
  }

  setLastMessageId(id) {
    this.lastMessageId = id;
  }
}

export { API, PollingManager };
