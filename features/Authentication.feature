Feature: Verify that eZ Toolbar is available only for authenticated users

  Background:
    Given There is a template "toolbar-layout.html.twig" that loads the code :
    """
    {{ ezToolbar(location is defined ? location : null) }}
    """
    And the following content view configuration block:
      """
      template: toolbar-layout.html.twig
      """
  Scenario: Should view ez Toolbar after successful login with admin user
    Given  I open Login page
    When I login as admin with password publish
    Then I should view ez Toolbar


  Scenario: Should view ez Toolbar after successful login with user having eztoolbar rights
    Given I open Login page
    And there is a user contrib whith password eZPlatform0 having rights eztoolbar
    When I login as contrib with password eZPlatform0
    Then I should view ez Toolbar


