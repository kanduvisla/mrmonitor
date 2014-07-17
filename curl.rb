require 'net/http'

urls = [
  {'link' => 'http://www.google.com/'},
  {'link' => 'http://www.yandex.ru/'},
  {'link' => 'http://www.baidu.com/'}
]

urls.each do |u|
  Thread.new do
    u['content'] = Net::HTTP.get( URI.parse(u['link']) )
    puts "Successfully requested #{u['link']}"

    if urls.all? {|u| u.has_key?("content") }
      puts "Fetched all urls!"
      exit
    end
  end
end

sleep