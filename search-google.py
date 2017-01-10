#!/usr/bin/env python2
# -*- coding: utf8 -*-

import os
import sys
import time
import random
import argparse
import MySQLdb

from selenium import webdriver
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.common.exceptions import NoSuchFrameException
from selenium.webdriver.common.keys import Keys

# If this script no longer fetches any results check the XPath

def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument('-s', '--search', help='Enter the search term')
    parser.add_argument('-p', '--pages', default='1', help='Enter how many pages to scrape (1 page = 100 results)')
    return parser.parse_args()

def start_browser():
    chromedriver = "/Users/konstantin/Downloads/chromedriver"
    os.environ["webdriver.chrome.driver"] = chromedriver
    br = webdriver.Chrome(chromedriver)
    br.implicitly_wait(10)
    return br

def get_ua():
    ua_list = ['Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36',
               'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36',
               'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14',
               'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
               'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
               'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0']
    ua = random.choice(ua_list)
    return ua

def scrape_results(br):
    links = br.find_elements_by_xpath("//h3[@class='r']/a[@href]")
    results = []
    for link in links:
        title = link.text.encode('utf8')
        url = link.get_attribute('href')
        title_url = (title, url)
        results.append(title_url)
    return results
def go_to_page(br, page_num, search_term):
    page_num = page_num - 1
    start_results = page_num * 10
    start_results = str(start_results)
    url = 'https://www.google.com/ncr#num=10&start='+start_results+'&q='+search_term
    print '[*] Fetching 10 results from page '+str(page_num+1)+' at '+url
    br.get(url)
    time.sleep(2)

def main():
    db = MySQLdb.connect(host="127.0.0.1",
                         user="seo_user",
                         passwd="seo_pass",
                         db="seo");
    cur = db.cursor()
    cur.execute("select queries.query from queries left join search on search.query=queries.id where search.query is null and LENGTH(queries.query) - LENGTH(REPLACE(queries.query, ' ', '')) > 5 order by rand()");
     
    br = start_browser()
    pages = 5

    for row in cur.fetchall():
      all_results = []
      search_term = row[0]
      for page_num in xrange(int(pages)):
          page_num = page_num+1 # since it starts at 0
          go_to_page(br, page_num, search_term)
          titles_urls = scrape_results(br)
          for title in titles_urls:
              all_results.append(title)
      pos = 0;
      for result in all_results:
          pos = pos+1;
          try:
             cur.execute("INSERT INTO search(url,query,position) SELECT %s,id,%s FROM queries WHERE query=%s",(result[1],pos,search_term));
             db.commit();
          except:
             db.rollback();
      print '['+search_term+']'
      time.sleep(250);
    db.close();
    br.close();
main()
