## Getting Started

1. Unpack the repo
2. Run: composer install (in the project root folder)
3. Run: php artisan fs:watch (to start the watcher)

And that's it! It will now track changes in the storage/app/watched folder

** PLEASE NOTE:

This repository was built as part of a technical assignment. The main goal was to demonstrate practical knowledge of Laravel, file operations, CLI commands, and external integrations. Some implementation choices were made for simplicity, clarity, and speed of delivery, and are not intended for production use as-is.

*** PLEASE NOTE: This project was made as a test for one of my previous clients.

WHAT IT DOES: This project tracks changes in project_root/storage/app/watched folder and handles them in certain ways. Tracking (scanning) occurs every 5 seconds and is reported to the console.

FOR EXAMPLE:

1. Each JPG file in the folder will automatically be optimized for the web and its quality reduced to 80%.

2. Each JSON file will be tested to see if it’s really a correct JSON file.

3. Each TXT file will be appended with random text from the API (https://baconipsum.com/api/?type=meat-and-filler)

4. Each ZIP file will automatically be extracted.

5. Each deleted file will be replaced with a random MEME from the API (https://meme-api.com/gimme)