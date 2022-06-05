from flask import Flask, jsonify, request

import depthmap

app = Flask(__name__)

process_threads = {}
tileset_build_threads = {}
image_root = ''
db_host = ''
db_user = ''
db_password = ''
db_name = ''


def full_fileset_build_worker():
    print('Processing image sets set for build...')
    set_images = []
    connection = depthmap.make_connection()
    cursor = connection.cursor()
    query = """
SELECT image_id, image_path, north, south, west, east
FROM images
WHERE images.status = 'ready'"""
    cursor.execute(query)
    connection.close()
    for row in cursor:
        set_images.append((row['image_path'], row['north'], row['south'], row['west'], row['east']))
    depthmap.build_tileset('full', set_images, image_root)
    connection = depthmap.make_connection()
    cursor = connection.cursor()
    query = "UPDATE image_set SET status = 'ready' WHERE id = {0}".format(image_set['id'])
    cursor.execute(query)
    connection.commit()
    connection.close()


@app.route('/util/get_sheet_code', methods=['GET'])
def get_sheet_code_value():
    north = request.args.get('north')
    south = request.args.get('south')
    west = request.args.get('west')
    east = request.args.get('east')
    north = depthmap.parse_angle_dms(north)
    south = depthmap.parse_angle_dms(south)
    west = depthmap.parse_angle_dms(west)
    east = depthmap.parse_angle_dms(east)
    return jsonify({'sheetCode': depthmap.get_sheet_code(north, south, west, east)})


# @app.route('/util/build_full_tileset', methods=['GET'])
# def build_full_tileset():
#

if __name__ == '__main__':
    settings_text = open('settings.json', 'r').read()
    settings_json = json.loads(settings_text)
    global image_root
    image_root = settings_json['imageroot']
    settings_db = settings_json['db']
    global db_host
    global db_user
    global db_password
    global db_name
    db_host = settings_db['host']
    db_user = settings_db['user']
    db_password = settings_db['password']
    db_name = settings_db['name']
    app.run(debug=True)
