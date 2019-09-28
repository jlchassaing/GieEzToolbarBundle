Feature: Verify that eZ Toolbar is available only for authenticated users

  @javascript
  Scenario: Should view ez Toolbar after successful login with admin user
    Given I open Login page
    When I login as admin with password publish
    Then I should view ez Toolbar

    @javascript
  Scenario: Should view ez Toolbar after successful login with user having eztoolbar rights
    Given I open Login page
    And there is a contrib user whith password publish having rights eztoolbar
    When I login as contrib with password publish
    Then I should view ez Toolbar


