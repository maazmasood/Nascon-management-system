const express = require('express');
const Event = require('../models/Event'); // Event model
const User = require('../models/User'); // User model
const router = express.Router();

// Event Registration Route
router.post('/register-event', async (req, res) => {
    try {
        const { userId, eventId } = req.body;

        // Check if the event exists
        const event = await Event.findById(eventId);
        if (!event) return res.status(404).json({ msg: 'Event not found' });

        // Check if the user exists
        const user = await User.findById(userId);
        if (!user) return res.status(404).json({ msg: 'User not found' });

        // Check if user is already registered for the event
        if (event.registeredUsers.includes(userId)) {
            return res.status(400).json({ msg: 'User already registered for this event' });
        }

        // Register user for the event
        event.registeredUsers.push(userId);
        await event.save();

        res.json({ msg: 'User successfully registered for the event', event });
    } catch (err) {
        res.status(500).json({ msg: 'Server Error' });
    }
});

module.exports = router;
