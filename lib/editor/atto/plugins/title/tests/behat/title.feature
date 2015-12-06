@editor @editor_atto @atto @atto_title
Feature: Atto title
  To format text in Atto, I need to add headings and other formating

  @javascript
  Scenario: Create a heading
    Given I log in as "admin"
    And I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the field "Description" to "How The Rock Has Made the WWE World Heavyweight Title More Important Than Ever"
    And I select the text in the "Description" Atto editor
    When I click on "Paragraph styles" "button"
    When I click on "Heading (large)" "link"
    And I press "Update profile"
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Plain text area"
    And I press "Save changes"
    And I follow "Edit profile"
    Then I should see "<h3>How The Rock"

  @javascript
  Scenario: Quote some text
    Given I log in as "admin"
    And I navigate to "Edit profile" node in "My profile settings"
    And I set the field "Text editor" to "Plain text area"
    And I set the field "Description" to "As a Lead JavaScript Engineer, I try to get my team to write as little JavaScript as possible."
    And I select the text in the "Description" Atto editor
    When I click on "Paragraph styles" "button"
    When I click on "Block quote" "link"
    And I press "Update profile"
    And I follow "Edit profile"
    Then I should see "<blockquote>As a Lead JavaScript Engineer,"
