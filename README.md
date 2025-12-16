# Interactive Timeline

A Moodle activity module that allows educators to create interactive, visually engaging timelines for presenting chronological content.

## Features

- **Visual Timeline Display**: Beautiful horizontal timeline with date markers and year separators
- **Rich Content Support**: WYSIWYG editor with file uploads (images, videos, documents)
- **Flexible Content Display**: Choose between tabbed view or popup modal for each event
- **Smart Navigation**: Automatic year navigation when timeline is scrollable
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Privacy Compliant**: Full GDPR compliance with Privacy API implementation

## Requirements

- Moodle 4.4 or higher
- PHP 8.1 or higher

## Installation

1. Extract the plugin archive to `/mod/timeline/` in your Moodle installation
2. Visit Site Administration → Notifications to complete the installation
3. The plugin is ready to use

## Usage

### For Teachers

1. Turn editing on in your course
2. Click "Add an activity or resource"
3. Select "Interactive Timeline"
4. Configure the timeline:
   - Enter a name and introduction
   - Add timeline events with dates, titles, and descriptions
   - Optionally add short descriptions for quick preview
   - Upload media files (images, videos) in the description
   - Choose content display type (tab or popup) for each event
5. Save and display

### For Students

- View the timeline with events arranged chronologically
- Click on event markers to view detailed content
- Navigate between years using the year navigation buttons
- Watch videos and view images directly in the timeline

## Configuration

The module provides the following settings per event:

- **Event Date**: Date/time picker for the event
- **Event Title**: Main title displayed on the timeline marker
- **Short Description**: Optional preview text shown on the marker
- **Description**: Full WYSIWYG editor with file upload support
- **Content Type**: Display in tab panel (default) or popup modal

## Privacy

This plugin implements the Moodle Privacy API. It stores:
- Timeline configuration data (course activity settings)
- Event content and files uploaded by teachers

No personal student data is collected or stored.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Author

**Raccoon Dev**  
Copyright © 2025

## Support

For bug reports and feature requests, please use the issue tracker.

