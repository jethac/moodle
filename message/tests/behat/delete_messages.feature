@core @core_message
Feature: Check that messages can be deleted
  In order to check a user can delete a message
  As a user
  I can delete a message

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | user1    | User      | 1        | user1@asd.com    |
      | user2    | User      | 2        | user2@asd.com    |
    And I log in as "admin"
    And I set the following administration settings values:
      | forceloginforprofiles | 0 |
    And I log out

  @javascript
  Scenario: Test basic functionality of deleting a message
    # Send two messages from User 1 to User 2.
    And I log in as "user1"
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "User 2"
    And I press "Search people and messages"
    And I follow "Picture of User 2"
    And I follow "Send a message"
    And I set the field "Message to send" to "Hey bud, what's happening?"
    And I press "Send message"
    And I wait "2" seconds
    And I follow "Send a message"
    And I set the field "Message to send" to "Whoops, forgot to mention that I drank all your beers. Lol."
    And I press "Send message"
    And I wait "2" seconds
    # Now, let's check that we can see these messages.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "User 2"
    And I press "Search people and messages"
    And I follow "Message history"
    And I should see "Hey bud, what's happening?"
    And I should see "Whoops, forgot to mention that I drank all your beers. Lol."
    # Confirm that there is a delete link next to each message.
    And "Delete" "link" should exist in the "#message_1" "css_element"
    And "Delete" "link" should exist in the "#message_2" "css_element"
    # Confirm that there is a confirmation box before deleting, and that when we cancel the messages remain.
    And I click on "Delete" "link" in the "#message_2" "css_element"
    And I press "Cancel"
    And I should see "Hey bud, what's happening?"
    And I should see "Whoops, forgot to mention that I drank all your beers. Lol."
    # Confirm we can delete a message and then can no longer see it.
    And I click on "Delete" "link" in the "#message_2" "css_element"
    And I press "Continue"
    And I should see "Hey bud, what's happening?"
    And I should not see "Whoops, forgot to mention that I drank all your beers. Lol."
    # Check that when we do a search for the keywords in the message we deleted nothing is returned.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "beers"
    And I press "Search people and messages"
    And I should see "Messages found: 0"
    # Check that we can still search the message that we did not delete.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "bud"
    And I press "Search people and messages"
    And I should see "Messages found: 1"
    And I log out
    # Log in as User 2 and send two replies.
    And I log in as "user2"
    And I navigate to "Messages" node in "My profile"
    And I follow "User 1 (2)"
    And I set the field with xpath "//textarea[@id='id_message']" to "Not much brah, just writing a behat test and communicating to myself."
    And I press "Send message"
    And I set the field with xpath "//textarea[@id='id_message']" to "Oh man, I was looking forward to those tonight!"
    And I press "Send message"
    # Confirm that we can see all messages.
    And I should see "Hey bud, what's happening?"
    And I should see "Whoops, forgot to mention that I drank all your beers. Lol."
    And I should see "Not much brah, just writing a behat test and communicating to myself."
    And I should see "Oh man, I was looking forward to those tonight!"
    # Confirm that there is a delete link next to each message.
    And "Delete" "link" should exist in the "#message_1" "css_element"
    And "Delete" "link" should exist in the "#message_2" "css_element"
    And "Delete" "link" should exist in the "#message_3" "css_element"
    And "Delete" "link" should exist in the "#message_4" "css_element"
    # Now, delete one of the messages that User 1 sent and one by User 2.
    And I click on "Delete" "link" in the "#message_1" "css_element"
    And I press "Continue"
    And I click on "Delete" "link" in the "#message_2" "css_element"
    And I press "Continue"
    # Confirm that the messages are no longer listed.
    And I should not see "Hey bud, what's happening?"
    And I should see "Whoops, forgot to mention that I drank all your beers. Lol."
    And I should not see "Not much brah, just writing a behat test and communicating to myself."
    And I should see "Oh man, I was looking forward to those tonight!"
    # Check that when we do a search for the keywords in the messages we deleted nothing is returned.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "bud"
    And I press "Search people and messages"
    And I should see "Messages found: 0"
    And I set the field "Search people and messages" to "brah"
    And I press "Search people and messages"
    And I should see "Messages found: 0"
    # Check that we can still search the message that we did not delete.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "Whoops"
    And I press "Search people and messages"
    And I should see "Messages found: 1"
    And I set the field "Search people and messages" to "tonight"
    And I press "Search people and messages"
    And I should see "Messages found: 1"
    And I log out

  @javascript
  Scenario: Check that we can prevent some users from deleting messages
    # Prevent the ability to delete messages.
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability                | permission |
      | moodle/site:deletemessage | Prevent   |
    # Send a message from the admin to User 1
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "User 1"
    And I press "Search people and messages"
    And I follow "Picture of User 1"
    And I follow "Send a message"
    And I set the field "Message to send" to "Hey there, this is the all-powerful administrator. Obey my commands."
    And I press "Send message"
    And I wait "2" seconds
    # Check they are still able to delete messages.
    And I navigate to "Messages" node in "My profile"
    And I set the field "Search people and messages" to "User 1"
    And I press "Search people and messages"
    And I follow "Message history"
    And I should see "Hey there, this is the all-powerful administrator. Obey my commands."
    And I click on "Delete" "link" in the "#message_1" "css_element"
    And I press "Continue"
    And I should not see "Hey there, this is the all-powerful administrator. Obey my commands."
    And I log out
    # Check that User 1 is unable to delete the message the admin sent.
    And I log in as "user1"
    And I navigate to "Messages" node in "My profile"
    And I follow "Admin User (1)"
    And "Delete" "link" should not exist in the "#message_1" "css_element"
