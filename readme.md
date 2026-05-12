# Prickled Herald Automation System 🦝

![Version](https://img.shields.io/badge/version-3.8-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-Plugin-20B2AA.svg)
![Tech Stack](https://img.shields.io/badge/Tech-PHP%20|%20JS%20|%20Python-orange.svg)

The **Prickled Herald Automation System** is a custom, full-stack WordPress plugin designed to automate the creation, formatting, and distribution of satirical news articles. 

By integrating the Google Gemini API directly into the WordPress admin dashboard and coupling it with a custom Python microservice, this system acts as an end-to-end publishing pipeline. It handles everything from generating structured JSON content (headlines, excerpts, and copy) to automatically rendering watermarked social media graphics and deploying them to Instagram.

## 🚀 System Architecture & Tech Stack

This project utilizes a decoupled architecture to separate content management from heavy image processing and third-party publishing:

* **Frontend Dashboard (JavaScript/jQuery):** Provides an asynchronous, interactive workspace within the WP Admin panel. It handles state management (generation, saving, media-previewing) without requiring page reloads.
* **Content Engine (PHP & Google Gemini API):** Leverages `wp_remote_post` to securely communicate with the Gemini API. Implements custom regex and fallback parsing logic to ensure AI responses are strictly formatted as valid JSON before inserting them into the WordPress database.
* **Media Microservice (Python REST API):** The PHP plugin communicates with a custom backend hosted at `api.prickledherald.com`. This service handles computationally heavy tasks like layering text over featured images (meme generation), applying visual palettes, and pushing payloads to the Instagram Graph API.

## ✨ Key Features

* **AI-Powered Generation:** Generates title, SEO tags, social media excerpts, and formatted HTML content based on current events.
* **Secure Credential Management:** Utilizes the native WordPress Options API to securely store the Gemini API key and system prompts in the MySQL database (`wp_options` table), ensuring no sensitive data is hardcoded into the application logic.
* **Custom Training Data Import:** Features a built-in parser to extract text from `.docx` files, allowing the user to feed historical articles into the Gemini prompt to maintain a consistent, satirical brand voice.
* **Robust JSON Parsing:** Includes fallback mechanisms to clean and extract JSON data even if the LLM hallucinates markdown formatting or conversational text.
* **Automated Social Media Pipeline:** Generates branded graphics from featured images and auto-posts them to Instagram upon article publication.
* **Dynamic Open Graph Injection:** Automatically injects optimized Facebook and Twitter metadata into the `<head>` of generated articles for perfect social sharing.

## 🛠️ Installation & Setup

1. **Install the Plugin:**
   * Upload the files to your WordPress `wp-content/plugins/` directory.
   * Activate the **PH Satire Generator Pro** plugin through the 'Plugins' menu in WordPress.
2. **Configure API Keys:**
   * Navigate to **Satire Generator > Training Data** in the WP admin sidebar.
   * Enter your [Google Gemini API Key](https://makersuite.google.com/app/apikey). The system will securely save this to your database.
3. **Set Up Training Data (Optional but Recommended):**
   * Upload previous `.docx` articles or paste text directly into the Training Data field to teach the AI your specific tone and formatting rules.

## 💻 Usage

1. Navigate to the **Satire Generator** workspace.
2. Enter a prompt or current event into the generation field (e.g., *"Tech CEO announces plan to replace all meetings with interpretive dance"*).
3. Click **Generate New Article**. The system will asynchronously fetch the content, parse the JSON, and populate the editor fields.
4. Set a Featured Image. This will unlock the **Meme Maker** interface.
5. Adjust the vertical offset, zoom, and color palette for the social media graphic.
6. Click **Save & Publish** to push the article live to WordPress and optionally auto-post the generated graphic to Instagram.

## 🧠 Development Notes

* **Author Attribution:** The plugin programmatically checks for and creates a custom `trashpanda` author account to ensure all generated content is correctly attributed and siloed from human authors.
* **Security:** Nonces are implemented across all AJAX calls (`ph_generate_content`, `ph_save_post`, `ph_trigger_meme`) to prevent unauthorized execution.

---
*Disclaimer: This tool was built to assist in the creative writing process. All AI-generated content is reviewed and edited by human writers before publication to ensure quality and comedic timing.*
