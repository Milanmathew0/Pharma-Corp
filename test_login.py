# Generated by Selenium IDE
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities

class TestLogin():
  def setup_method(self, method):
    self.driver = webdriver.Chrome()
    self.vars = {}
  
  def teardown_method(self, method):
    self.driver.quit()
  
  def test_login(self):
    self.driver.get("http://localhost/new/")
    self.driver.set_window_size(1337, 752)
    self.driver.find_element(By.LINK_TEXT, "Login").click()
    self.driver.find_element(By.ID, "email").click()
    self.driver.find_element(By.ID, "email").send_keys("milan3@gmail.com")
    self.driver.find_element(By.ID, "email").send_keys("milan3@gmail.com")
    self.driver.find_element(By.ID, "email").send_keys(Keys.DOWN)
    self.driver.find_element(By.ID, "email").send_keys(Keys.TAB)
    self.driver.find_element(By.ID, "password").send_keys("Milan@07")
    self.driver.find_element(By.CSS_SELECTOR, ".btn-login").click()
  
