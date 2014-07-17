require 'csv'
require 'net/http'

# Check if the URL is working or not
def working_url(url_str)
  	url = URI.parse(url_str)
  	Net::HTTP.start(url.host, url.port) do |http|
    	# http.head(url.request_uri).code == '200'
    	true
  	end
rescue
  	false
end

# Function to parse urls
def parseUrls(urls)
	redirectsFound = false
	urls.each do |u|
		# Check which response is correct:
		if u['response_www'] == false
			u['success'] = false
			u['message'] = 'Www domain does not exist'
		elsif u['response_non_www'] == false
			u['success'] = false
			u['message'] = 'Non-www domain does not exist'
		else
			# www and non www both exist. Now check if one is 200 and the other is a redirect
			if u['response_www'].code == "200"
				u['response'] = u['response_www']
				u['duration'] = u['duration_www']
				# Check if redirect is correct:
				u['redirect_ok'] = u['response_non_www'].header['location'].sub(/(\/)+$/,'') == u['www']
			elsif u['response_non_www'].code == "200"
				u['response'] = u['response_non_www']
				u['duration'] = u['duration_non_www']
				# Check if redirect is correct:
				u['redirect_ok'] = u['response_www'].header['location'].sub(/(\/)+$/,'') == u['non_www']				
			else
				# Www and non-www are not 200
				# Follow both redirects until 200
				redirectsFound = true
				u['redirect'] += 1
				# www
				if u['response_www'].header['location'].start_with?('http')
					u['www'] = u['response_www'].header['location']
				else 
					u['www'] << '/' << u['response_www'].header['location']
				end
				# non www
				if u['response_non_www'].header['location'].start_with?('http')
					u['non_www'] = u['response_non_www'].header['location']
				else 
					u['non_www'] << '/' << u['response_non_www'].header['location']
				end
				u.delete('response_www')
				u.delete('response_non_www')
			end

			if u.has_key?('response')
				u['code'] = u['response'].code
				# u.delete('response_www')
				# u.delete('response_non_www')
				# u.delete('duration_www')
				# u.delete('duration_non_www')
			end
		end
=begin
		u['code'] = u['response'].code
		# If code is a redirect, make a new request:
		if u['code'] == "301" || u['code'] == "302"
			redirectsFound = true
			# Follow redirect
			u['redirect'] += 1
			u.delete('response')
		end
=end		
	end

	if redirectsFound
		puts "Found redirects. Following ..."
		asyncRequests(urls)
	else
		puts "No redirects found. Halting ..."
	end
end

# Asynchronous URL requests
def asyncRequests(urls)
	puts "Requesting #{urls.length} urls ..."
	urls.each do |u|
		if ! u.has_key?('response')
			Thread.new do
				# Remove trailing slash:
				u['link'].sub!(/(\/)+$/,'')

				# www request:
				time_start = Time.now.to_f
				if ! u.has_key?('www')
					u['www'] = u['link']
				end
				if working_url(u['www'])
		    		u['response_www'] = Net::HTTP.get_response( URI.parse(u['www']) )
		    	else
		    		u['response_www'] = false
		    	end
		    	u['duration_www'] = Time.now.to_f - time_start
				puts u['www']

		    	# non-www request
				time_start = Time.now.to_f
				if ! u.has_key?('non_www')
					u['non_www'] = u['link'].sub(/\/\/www\./, '//')
				end
				if working_url(u['non_www'])
			    	u['response_non_www'] = Net::HTTP.get_response( URI.parse(u['non_www']) )
		    	else
		    		u['response_non_www'] = false
		    	end
		    	u['duration_non_www'] = Time.now.to_f - time_start
				puts u['non_www']

		    	# Default variables
				u['redirect'] = 0
				u['success'] = true

		    	if urls.all? {|u| u.has_key?('response_www') and u.has_key?('response_non_www') }
		      		puts 'Fetched all urls!'
		      		# Parse the resulting data
		      		parseUrls(urls)
		      		puts "Parsing complete"
		      		if urls.all? {|u| u.has_key?('response')}
		      			puts urls
		      			exit
		      		end
		    	end
		  	end
	  	end
	end
end

# Get the URL's from the CSV document:
urls = []

CSV.foreach('sites.csv') do |row|
	if ! row.empty?
		urls.push({'link' => row[0]})
	end
end

# Do multi threaded curl requests:
asyncRequests(urls)

# Sort them:
urls.sort! { |a,b| a['link'] <=> b['link'] }

sleep