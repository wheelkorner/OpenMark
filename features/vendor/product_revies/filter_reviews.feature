@product_reviews
Feature: Vendor can filter reviews of his products
  In order to filter reviews.
  As a Vendor I want to filter reviews

  Background:
    Given the store operates on a single channel in "United States"
    And the store operates in "Poland"
    And the store has customer "Alex Holannd" with email "alex@honnold.pl"
    And there is a "verified" vendor user "kim@jain.pl" registered in country "PL"
    And I am logged in as "kim@jain.pl"
    And There is a product with variant code "Quickdraws-x6" owned by logged in vendor
    And this product has a new review titled "Good" and rated 4 added by customer "alex@honnold.pl"
    And there is a "verified" vendor user "addam@ondra.pl" registered in country "PL"
    And I am logged in as "addam@ondra.pl"


  @ui
  Scenario: Filtering new reviews of a product
    Given There is a product with variant code "Quickdraws-x5" owned by logged in vendor
    And this product has a new review titled "Good" and rated 4 added by customer "alex@honnold.pl"
    And this product has one review from customer "kim@jain.pl"
    And I am on "/en_US/account/vendor/product-reviews"
    When I select "New" from "criteria[status]"
    And I click "Filter"
    Then I should see "1" reviews

  @ui
  Scenario: Filtering accepted reviews of a product
    Given There is a product with variant code "Quickdraws-x5" owned by logged in vendor
    And this product has a new review titled "The best" and rated 5 added by customer "addam@ondra.pl"
    And this product has one review from customer "alex@honnold.pl"
    And this product has one review from customer "kim@jain.pl"
    And I am on "/en_US/account/vendor/product-reviews"
    When I select "Accepted" from "criteria[status]"
    And I click "Filter"
    Then I should see "2" reviews

  @ui
  Scenario: Filtering accepted reviews of a product
    Given There is a product with variant code "Quickdraws-x5" owned by logged in vendor
    And this product also has review rated 2 which is rejected
    And this product also has review rated 1 which is rejected
    And this product also has review rated 1 which is rejected
    And I am on "/en_US/account/vendor/product-reviews"
    When I select "Rejected" from "criteria[status]"
    And I click "Filter"
    Then I should see "3" reviews

  @ui
  Scenario: Filtering by rating
    Given There is a product with variant code "Helmet" owned by logged in vendor
    And this product has one review from customer "alex@honnold.pl"
    And this product has a new review titled "No good" and rated 1 added by customer "kim@jain.pl"
    And There is a product with variant code "Helmet XL" owned by logged in vendor
    And this product has a new review titled "Bad" and rated 1 added by customer "kim@jain.pl"
    And I am on "/en_US/account/vendor/product-reviews"
    When I select "1" from "criteria[rating]"
    And I click "Filter"
    Then I should see "2" reviews
