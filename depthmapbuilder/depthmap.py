import json
import math
import os
import re
import threading
import time
from enum import Enum
from math import *

import PIL
import cv2
import numpy as np
import pymysql
from PIL import Image
from matplotlib import pyplot as plt
from pymysql.cursors import DictCursor
from pytesseract import pytesseract

global image_root
image_root = ''
db_host = ''
db_user = ''
db_password = ''
db_name = ''
loop_timeout = 2


class MapScale(Enum):
    MAP_1M = 1
    MAP_500K = 2
    MAP_300K = 3
    MAP_200K = 4
    MAP_100K = 5
    MAP_50K = 6
    MAP_25K = 7
    MAP_10K = 8
    MAP_5K = 9
    MAP_2K = 10
    MAP_1K = 11
    MAP_500 = 12


lat_scales = {
    (4, 0, 0): MapScale.MAP_1M,
    (2, 0, 0): MapScale.MAP_500K,
    (1, 20, 0): MapScale.MAP_300K,
    (0, 40, 0): MapScale.MAP_200K,
    (0, 20, 0): MapScale.MAP_100K,
    (0, 10, 0): MapScale.MAP_50K,
    (0, 5, 0): MapScale.MAP_25K,
    (0, 2, 30): MapScale.MAP_10K,
    (0, 1, 15): MapScale.MAP_5K,
    (0, 0, 25): MapScale.MAP_2K,
}

anums = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1]
rnums = "M CM D CD C XC L XL X IX V IV I".split()


def frac(x):
    return x - math.trunc(x)


def clamp(x, a, b):
    return max(min(x, b), a)


def deg_to_dms_int(deg):
    degs = int(deg)
    mins = int(math.trunc(frac(deg) * 60))
    secs = int(math.trunc(frac(frac(deg) * 60) * 60))
    return (degs, mins, secs)


def dms_to_deg(dms):
    degs, mins, secs = dms
    return degs + mins / 60.0 + secs / 3600.0


def to_roman(x):
    ret = []
    for a, r in zip(anums, rnums):
        n, x = divmod(x, a)
        ret.append(r * n)
    return ''.join(ret)


def parse_angle_dms(dms_text):
    find_result = re.findall(r'((\d+)(?: (\d+)(?: (\d+))?)?)', dms_text)
    if len(find_result) == 0:
        find_result = re.findall(r'((\d+)°(?:(\d+)\'(?:(\d+)\")?)?)', dms_text)
    if len(find_result) == 0:
        return 0
    divisors = [1, 60.0, 3600.0]
    result_sum = 0
    for elem in list(find_result[0])[1:]:
        if len(elem) > 0:
            result_sum += float(elem) / divisors.pop(0)
    return result_sum


def get_sheet_code(north, south, west, east):
    if abs(west) > 180 or abs(east) > 180:
        return None
    north = round(north, 10)
    south = round(south, 10)
    west = round(west, 10)
    east = round(east, 10)
    result = ''
    lat_index = 0
    lat_letter = ''
    lon_index = 0
    lon_sign = abs(east - west) / (east - west)
    lower_bound = min(north, south)
    if lower_bound > 88.0:
        lat_letter = 'Z'
    else:
        lat_letter = chr(ord('A') + int(lower_bound // 4))
    lat_index = int(lower_bound // 4)
    lat_range = abs(north - south)
    lat_scale = None
    for lat_scale_key in lat_scales.keys():
        if abs(dms_to_deg(lat_scale_key) - lat_range) < 0.0000001:
            lat_scale = lat_scales[lat_scale_key]
            break
    if lat_scale is None:
        return None
    lon_index = int(31 + lon_sign * west // 6)
    if lat_scale == MapScale.MAP_1M:
        if lower_bound > 88:
            result = lat_letter
        elif lower_bound > 76:
            lon_index_display = ','.join([lon_index + i for i in range(0, 3)])
            result = lat_letter + '-' + lon_index_display
        elif lower_bound > 60:
            lon_index_display = ','.join([lon_index, lon_index + i])
            result = lat_letter + '-' + lon_index_display
        else:
            lon_index_display = str(lon_index)
            result = lat_letter + '-' + lon_index_display
    elif lat_scale == MapScale.MAP_500K:
        result = lat_letter + '-' + str(lon_index) + '-'
        lat_half = lat_index * 4.0 + 2.0
        if lower_bound >= lat_half:
            if lower_bound > 60:
                result += 'А,Б'
            else:
                lon_half = -180.0 + lon_index * 6.0 + 3.0
                if (west * lon_sign) >= lon_half:
                    result += 'Б'
                else:
                    result += 'А'
        else:
            if lower_bound > 60:
                result += 'В,Г'
            else:
                lon_half = -180.0 + lon_index * 6.0 + 3.0
                if (west * lon_sign) >= lon_half:
                    result += 'Г'
                else:
                    result += 'В'
    elif lat_scale == MapScale.MAP_300K:
        result = None
    elif lat_scale == MapScale.MAP_200K:
        result = lat_letter + '-' + str(lon_index) + '-'
        cell_j = int(180 + west * lon_sign - (lon_index - 1) * 6.0)
        cell_i = int(5 - (lower_bound - lat_index * 4.0) // 0.66666666)
        ordinal = 1 + (cell_j + cell_i * 6)
        if lower_bound >= 76:
            result += ','.join([to_roman(ordinal + i) for i in range(0, 3)])
        elif lower_bound >= 60:
            result += ','.join([to_roman(ordinal + i) for i in range(0, 2)])
        else:
            result += to_roman(ordinal)
    elif lat_scale == MapScale.MAP_100K:
        result = lat_letter + '-' + str(lon_index) + '-'
        cell_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.5)
        cell_i = int(11 - (lower_bound - lat_index * 4.0) // 0.33333333)
        ordinal = 1 + (cell_j + cell_i * 12)
        if lower_bound >= 76:
            result += ','.join([str(ordinal + i) for i in range(0, 4)])
        elif lower_bound >= 60:
            result += ','.join([str(ordinal + i) for i in range(0, 2)])
        else:
            result += str(ordinal)
    elif lat_scale == MapScale.MAP_50K:
        result = lat_letter + '-' + str(lon_index) + '-'
        cell_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.5)
        cell_i = int(11 - (lower_bound - lat_index * 4.0) // 0.33333333)
        ordinal = 1 + (cell_j + cell_i * 12)
        if lower_bound >= 76:
            result += ','.join([str(ordinal + i) for i in range(0, 4)])
        elif lower_bound >= 60:
            result += ','.join([str(ordinal + i) for i in range(0, 2)])
        else:
            result += str(ordinal)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.25) % 2
        half_i = int(23 - (lower_bound - lat_index * 4.0) // 0.16666666) % 2
        result += '-' + chr(ord('А') + half_i * 2 + half_j)
    elif lat_scale == MapScale.MAP_25K:
        result = lat_letter + '-' + str(lon_index) + '-'
        cell_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.5)
        cell_i = int(11 - (lower_bound - lat_index * 4.0) // 0.33333333)
        ordinal = 1 + (cell_j + cell_i * 12)
        if lower_bound >= 76:
            result += ','.join([str(ordinal + i) for i in range(0, 4)])
        elif lower_bound >= 60:
            result += ','.join([str(ordinal + i) for i in range(0, 2)])
        else:
            result += str(ordinal)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.25) % 2
        half_i = int(23 - (lower_bound - lat_index * 4.0) // 0.16666666) % 2
        result += '-' + chr(ord('А') + half_i * 2 + half_j)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.125) % 2
        half_i = int(47 - (lower_bound - lat_index * 4.0) // 0.08333333) % 2
        result += '-' + chr(ord('а') + half_i * 2 + half_j)
    elif lat_scale == MapScale.MAP_10K:
        result = lat_letter + '-' + str(lon_index) + '-'
        cell_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.5)
        cell_i = int(11 - (lower_bound - lat_index * 4.0) // 0.33333333)
        ordinal = 1 + (cell_j + cell_i * 12)
        if lower_bound >= 76:
            result += ','.join([str(ordinal + i) for i in range(0, 4)])
        elif lower_bound >= 60:
            result += ','.join([str(ordinal + i) for i in range(0, 2)])
        else:
            result += str(ordinal)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.25) % 2
        half_i = int(23 - (lower_bound - lat_index * 4.0) // 0.16666666) % 2
        result += '-' + chr(ord('А') + half_i * 2 + half_j)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.125) % 2
        half_i = int(47 - (lower_bound - lat_index * 4.0) // 0.08333333) % 2
        result += '-' + chr(ord('а') + half_i * 2 + half_j)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.125) % 2
        half_i = int(47 - (lower_bound - lat_index * 4.0) // 0.08333333) % 2
        result += '-' + chr(ord('а') + half_i * 2 + half_j)
        half_j = int((180 + west * lon_sign - (lon_index - 1) * 6.0) / 0.125) % 2
        half_i = int(95 - (lower_bound - lat_index * 4.0) // 0.04166664) % 2
        result += '-' + str(1 + half_i * 2 + half_j)
    elif lat_scale == MapScale.MAP_5K:
        result = None
    elif lat_scale == MapScale.MAP_2K:
        result = None
    return result


def lon_to_wmerc(lon, zoom_level, tile_size=256):
    return (tile_size / (2.0 * pi)) * (2 ** zoom_level) * (radians(lon) + pi)


def lat_to_wmerc(lat, zoom_level, tile_size=256):
    return (tile_size / (2.0 * pi)) * (2 ** zoom_level) * (pi - log(tan(pi / 4.0 + radians(lat) / 2.0)))


def get_x_tile(lon, zoom_level):
    return int(((lon + 180) / 360) * 2 ** zoom_level)


def get_y_tile(lat, zoom_level):
    return int((1 - log(tan(radians(lat)) + 1 / cos(radians(lat))) / pi) * 2 ** (zoom_level - 1))


def make_connection():
    return pymysql.connect(host=db_host, user=db_user, password=db_password, db=db_name, charset='utf8mb4',
                           cursorclass=DictCursor)


def point_distance(point1, point2):
    x1, y1 = point1
    x2, y2 = point2
    return math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2)


def find_intersection_raw(line1, line2):
    x1, y1, x2, y2 = line1
    x3, y3, x4, y4 = line2
    px = ((x1 * y2 - y1 * x2) * (x3 - x4) - (x1 - x2) * (x3 * y4 - y3 * x4)) / \
         ((x1 - x2) * (y3 - y4) - (y1 - y2) * (x3 - x4))
    py = ((x1 * y2 - y1 * x2) * (y3 - y4) - (y1 - y2) * (x3 * y4 - y3 * x4)) / \
         ((x1 - x2) * (y3 - y4) - (y1 - y2) * (x3 - x4))
    return px, py


def find_line_bounds(line):
    rho, theta = line[0]
    a = math.cos(theta)
    b = math.sin(theta)
    x0 = a * rho
    y0 = b * rho
    x1 = int(x0 + 1000 * (-b))
    y1 = int(y0 + 1000 * (a))
    x2 = int(x0 - 1000 * (-b))
    y2 = int(y0 - 1000 * (a))
    return (x1, y1, x2, y2)


def segment_lines_par(lines, delta):
    h_lines = []
    v_lines = []
    for line in lines:
        theta = line[0][1]
        if abs(theta) < delta:
            v_lines.append(line)
        elif abs(np.pi / 2 - theta) < delta:
            h_lines.append(line)
    return h_lines, v_lines


def cluster_points(points, nclusters):
    criteria = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 10, 1.0)
    _, _, centers = cv2.kmeans(points, nclusters, None, criteria, 10, cv2.KMEANS_PP_CENTERS)
    return centers


def image_crop(imgname, coords):
    image = cv2.cvtColor(np.array(Image.open(image_root + '/orig/' + imgname).convert('RGB')), cv2.COLOR_RGB2BGR)
    pointsX = []
    pointsY = []
    for point in coords:
        x, y = point
        pointsX.append(x)
        pointsY.append(y)

    xMax = max(pointsX)
    xMin = min(pointsX)
    yMax = max(pointsY)
    yMin = min(pointsY)
    wNew = xMax - xMin
    hNew = yMax - yMin
    matDest = np.array([
        [0, 0],
        [wNew - 1, 0],
        [wNew - 1, hNew - 1],
        [0, hNew - 1]
    ], dtype='float32')
    rect = np.float32([[[x, y] for x, y in coords]])
    matTrans = cv2.getPerspectiveTransform(rect, matDest)
    warped = cv2.warpPerspective(image, matTrans, (wNew, hNew))
    cv2.imwrite(image_root + '/cropped/' + imgname, warped)


def autocrop_and_detect(imgname):
    image_root = '/home/kamotora/IdeaProjects/maga/python_example/depthmapbuilder/test'
    method_result = []
    image = cv2.cvtColor(np.array(PIL.Image.open(image_root + '/orig/' + imgname).convert('RGB')), cv2.COLOR_RGB2BGR)

    templateNW = cv2.imread('templates/template_nw.png')
    templateNE = cv2.imread('templates/template_ne.png')
    templateSW = cv2.imread('templates/template_sw.png')
    templateSE = cv2.imread('templates/template_se.png')

    imageGray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    templateNWGray = cv2.cvtColor(templateNW, cv2.COLOR_BGR2GRAY)
    templateNEGray = cv2.cvtColor(templateNE, cv2.COLOR_BGR2GRAY)
    templateSWGray = cv2.cvtColor(templateSW, cv2.COLOR_BGR2GRAY)
    templateSEGray = cv2.cvtColor(templateSE, cv2.COLOR_BGR2GRAY)
    imageGrayN = cv2.medianBlur(imageGray, 1)
    img_h, img_w = imageGray.shape
    imageGrayN = cv2.threshold(imageGrayN, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)[1]

    templates = [templateNWGray, templateNEGray, templateSEGray, templateSWGray]
    # [isEast, isSouth]
    sides = [(False, False),
             (True, False),
             (True, True),
             (False, True)]
    centers = []
    bounds = []
    counter = 0
    template_scale_rel = 0.04
    # find corners
    for template in templates:
        h, w = template.shape
        scale_factor = w / (img_w * template_scale_rel)
        templateScaled = cv2.resize(template, (0, 0), fx=scale_factor, fy=scale_factor)
        h, w = templateScaled.shape
        result = cv2.matchTemplate(imageGrayN, templateScaled, cv2.TM_CCOEFF_NORMED)
        threshold = 0.250
        loc = np.where(result >= threshold)
        points = list(zip(*loc[::-1]))
        if len(points) == 0:
            print("Not found matching points... ")
            continue
        top_left = None
        isEast, isSouth = sides[counter]
        if isEast:
            if isSouth:
                top_left = \
                    sorted(points, key=lambda point: point_distance((img_w, img_h), (point[0] + w, point[1] + h)))[0]
            else:
                top_left = sorted(points, key=lambda point: point_distance((img_w, 0), (point[0] + w, point[1])))[0]
        else:
            if isSouth:
                top_left = sorted(points, key=lambda point: point_distance((0, img_h), (point[0], point[1] + h)))[0]
            else:
                top_left = sorted(points, key=lambda point: point_distance((0, 0), point))[0]
        bottom_right = (top_left[0] + w, top_left[1] + h)
        bounds.append((top_left, bottom_right))
        centers.append((top_left[0] + w // 2, top_left[1] + h // 2))
        counter += 1
        cv2.rectangle(image, top_left, bottom_right, 150, thickness=5)
    #     windowWidth = 5000
    #     windowHeight = 3000
    #     newWidth = img_w
    #     newHeight = img_h
    #     if newWidth > windowWidth or newHeight > windowHeight:
    #         step = max(newWidth / windowWidth, newHeight / windowHeight)
    #         newWidth /= step
    #         newHeight /= step
    #     resized = cv2.resize(image, (int(newWidth), int(newHeight)))
    #     cv2.imwrite(image_root + '/cropped/' + imgname, resized)
    # return (0,0)
    # determine if bounds are rectangular
    isRect = True
    if len(bounds) == 4:
        for i in range(0, 4):
            center1 = centers[i]
            center2 = centers[(i + 1) % 4]
            center3 = centers[(i + 2) % 4]
            a = point_distance(center1, center2)
            b = point_distance(center2, center3)
            c = point_distance(center1, center3)
            delta = abs(1 - (a ** 2 + b ** 2) / c ** 2)
            if (delta > 0.1):
                isRect = isRect and False
        print('Is rect: ', isRect)
        print(bounds)

    quadPoints = []
    quadStrings = []
    custom_config = '--oem 3 --psm 6'
    i = 0
    if isRect:
        for corner, side in zip(bounds, sides):
            topLeft, bottomRight = corner
            isEast, isSouth = side
            xTop, yTop = topLeft
            xBottom, yBottom = bottomRight
            w = xBottom - xTop
            h = yBottom - yTop
            # Увеличить область
            if isEast:
                xTop -= w
            else:
                xBottom += w
            if isSouth:
                yTop -= h
            else:
                yBottom += h
            centerPoint = ((xTop + xBottom) // 2, (yTop + yBottom) // 2)
            imageCropped = imageGray[yTop:yBottom, xTop:xBottom]
            # detect center points
            imageCropped = cv2.adaptiveThreshold(imageCropped, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY,
                                                 7, 15)
            imageCanny = cv2.Canny(imageCropped, 60, 180)
            # detect lines using Hough transform
            lines = cv2.HoughLines(imageCanny, rho=3, theta=(np.pi / 180), threshold=160)
            if lines is not None:
                linesH, linesV = segment_lines_par(lines, 0.1)
                for line in linesH + linesV:
                    rho = line[0][0]
                    theta = line[0][1]
                    a = math.cos(theta)
                    b = math.sin(theta)
                    x0 = a * rho
                    y0 = b * rho
                    pt1 = (int(x0 + 1000 * (-b)), int(y0 + 1000 * (a)))
                    pt2 = (int(x0 - 1000 * (-b)), int(y0 - 1000 * (a)))
                    cv2.line(imageCropped, pt1, pt2, 192, 1, cv2.LINE_AA)
                ptx = []
                pty = []
                for lineH in linesH:
                    for lineV in linesV:
                        px, py = find_intersection_raw(find_line_bounds(lineH), find_line_bounds(lineV))
                        if not math.isnan(px) and not math.isnan(py):
                            ptx.append(px)
                            pty.append(py)

                cv2.imwrite(image_root + '/cropped/'+imgname + str(i) + '.jpg', imageCropped)
                pts = np.float32(np.column_stack((ptx, pty)))
                num_clusters = 10
                centers = cluster_points(pts, min(len(pts), num_clusters))

                for cx, cy in centers:
                    cx = np.round(cx).astype(int)
                    cy = np.round(cy).astype(int)
                    cv2.circle(imageCropped, (cx, cy), radius=4, color=192, thickness=-1)
                # searching for point closest to the selection center
                distanceMin = float('inf')
                closestPoint = None
                for center in centers:
                    distance = point_distance((w, h), center)
                    if distance < distanceMin:
                        closestPoint = center
                        distanceMin = distance
                cv2.circle(imageCropped, tuple(closestPoint.astype(int)), radius=4, color=128, thickness=-1)
                x0, y0 = closestPoint
                quadPoints.append((int(x0) + xTop, int(y0) + yTop))
                # show result
                # plt.figure()
                # plt.imshow(imageCropped)
                # cleanup cropped image
                dilatation_size = 1
                horizontalStructure = cv2.getStructuringElement(cv2.MORPH_RECT, (w // 32, 1))
                verticalStructure = cv2.getStructuringElement(cv2.MORPH_RECT, (1, h // 26))
                imageCropped = imageGray[yTop:yBottom, xTop:xBottom]
                imageCropped = cv2.GaussianBlur(imageCropped, (3, 3), 0.5)
                # Заполняем часть карты внутри рамки белым цветом, чтобы не мешало при чтении символов
                fillTop = None
                fillBottom = None
                if not isEast and not isSouth:
                    fillTop = tuple(closestPoint)
                    fillBottom = (2 * w - 1, 2 * h - 1)
                elif isEast and not isSouth:
                    fillTop = (0, closestPoint[1])
                    fillBottom = (closestPoint[0], 2 * h - 1)
                elif isEast and isSouth:
                    fillTop = (0, 0)
                    fillBottom = tuple(closestPoint)
                elif not isEast and isSouth:
                    fillTop = (closestPoint[0], 0)
                    fillBottom = (2 * w - 1, closestPoint[1])
                cv2.rectangle(imageCropped, tuple(map(lambda _x: int(_x), fillTop)),
                              tuple(map(lambda _x: int(_x), fillBottom)), 255, thickness=cv2.FILLED)
                i += 1
                txt = pytesseract.image_to_string(imageCropped, config=custom_config, lang='eng+equ')
                quadStrings.append(''.join(re.findall(r'[\d\'\"°\n]+', txt)))
    else:
        print('Unable to autocrop ' + imgname)
        return (1, None)
    print(quadPoints)
    if len(quadPoints) == 4:
        method_result.append(quadPoints)
        imageTransformed = image.copy()
        pointsX = []
        pointsY = []
        for point in quadPoints:
            # cv2.circle(image, point, 5, 150, thickness=3)
            # cv2.rectangle(image, (xTop, yTop), (xBottom, yBottom), 150, thickness=5)
            # cv2.imwrite(image_root + '/cropped/100k' + str(point) + '.jpg', image)
            x, y = point
            pointsX.append(x)
            pointsY.append(y)

        xMax = max(pointsX)
        xMin = min(pointsX)
        yMax = max(pointsY)
        yMin = min(pointsY)
        wNew = xMax - xMin
        hNew = yMax - yMin
        matDest = np.array([
            [0, 0],
            [wNew - 1, 0],
            [wNew - 1, hNew - 1],
            [0, hNew - 1]
        ], dtype='float32')
        rect = np.float32([[[x, y] for x, y in quadPoints]])
        matTrans = cv2.getPerspectiveTransform(rect, matDest)
        warped = cv2.warpPerspective(imageTransformed, matTrans, (wNew, hNew))
        cv2.imwrite(image_root + '/cropped/' + imgname, warped)
    detected_coords = [[], [], [], []]
    for i in range(0, 4):
        isEast, isSouth = sides[i]
        print('Detected: ', quadStrings[i])
        strings = [line for line in quadStrings[i].split('\n') if len(re.findall(r'[\d]+', line)) > 0]
        if isSouth:
            x_pos = -1
            for j in range(0, len(strings)):
                if len(re.findall(r'[\d]+', strings[j])) >= 2 and len(re.findall(r'[\d]+', strings[j])) <= 3:
                    x_pos = j
                    break
            if x_pos != -1:
                lon_numbers = []
                lon_string = ''
                for num in re.findall(r'\d+', strings[x_pos]):
                    if len(num) == 4:
                        lon_numbers.append(int(num[0:1]))
                        lon_numbers.append(int(num[2:3]))
                    else:
                        lon_numbers.append(int(num))
                if len(lon_numbers) == 3:
                    lon_string = '{0}°{1}\'{2}\"'.format(lon_numbers[0], lon_numbers[1], lon_numbers[2])
                else:
                    lon_string = '{0}°{1}\''.format(lon_numbers[0], lon_numbers[1])
                if isEast:
                    if lon_string not in detected_coords[3]:
                        detected_coords[3].append(lon_string)
                else:
                    if lon_string not in detected_coords[2]:
                        detected_coords[2].append(lon_string)
                lat_numbers = []
                lat_string = ''
                for num_string in strings[0:(x_pos - 1)]:
                    for num in re.findall(r'\d+', num_string):
                        lat_numbers.append(num)
                if len(lat_numbers) > 0:
                    if len(lat_numbers) == 3:
                        lat_string = '{0}°{1}\'{2}\"'.format(lat_numbers[0], lat_numbers[1], lat_numbers[2])
                    elif len(lat_numbers) == 1:
                        lat_string = '{0}°0\'0\"'.format(lat_numbers[0])
                    else:
                        lat_string = '{0}°{1}\'0\"'.format(lat_numbers[0], lat_numbers[1])
                    if lat_string not in detected_coords[1]:
                        detected_coords[1].append(lat_string)
        else:
            x_pos = -1
            for j in range(len(strings) - 1, 0, -1):
                if len(re.findall(r'[\d]+', strings[j])) >= 2 and len(re.findall(r'[\d]+', strings[j])) <= 3:
                    x_pos = j
                    break
            if x_pos != -1:
                lon_numbers = []
                lon_string = ''
                for num in re.findall(r'[\d]+', strings[x_pos]):
                    if len(num) == 4:
                        lon_numbers.append(int(num[0:1]))
                        lon_numbers.append(int(num[2:3]))
                    else:
                        lon_numbers.append(int(num))
                if len(lon_numbers) == 3:
                    lon_string = '{0}°{1}\'{2}\"'.format(lon_numbers[0], lon_numbers[1], lon_numbers[2])
                else:
                    lon_string = '{0}°{1}\''.format(lon_numbers[0], lon_numbers[1])
                if isEast:
                    if lon_string not in detected_coords[3]:
                        detected_coords[3].append(lon_string)
                else:
                    if lon_string not in detected_coords[2]:
                        detected_coords[2].append(lon_string)
                lat_numbers = []
                lat_string = ''
                for num_string in strings[(x_pos + 1):(len(strings) - 1)]:
                    for num in re.findall(r'\d+', num_string):
                        lat_numbers.append(num)
                if len(lat_numbers) > 0:
                    if len(lat_numbers) == 3:
                        lat_string = '{0}°{1}\'{2}\"'.format(lat_numbers[0], lat_numbers[1], lat_numbers[2])
                    elif len(lat_numbers) == 1:
                        lat_string = '{0}°0\'0\"'.format(lat_numbers[0])
                    else:
                        lat_string = '{0}°{1}\'0\"'.format(lat_numbers[0], lat_numbers[1])
                    if lat_string not in detected_coords[0]:
                        detected_coords[0].append(lat_string)
        method_result.append(detected_coords)
    return (0, method_result)


def build_tileset(tileset_id, tileset_images, image_root=image_root):
    lat_hemi = 85.0511
    tile_size = 256
    base_lon_range = 360.0
    base_lat_range = 2 * lat_hemi
    zoom_levels = 23
    images_parsed = []
    images_bounds = {}
    images_limits = {}
    print("Precalculating image boundaries...")
    for image in tileset_images:
        image_path, north, south, west, east = image
        north = parse_angle_dms(north)
        south = parse_angle_dms(south)
        west = parse_angle_dms(west)
        east = parse_angle_dms(east)
        if west > east:
            west *= -1
            east *= -1
        if north < south:
            north *= -1
            south *= -1
        images_parsed.append((image_path, north, south, west, east))
        images_bounds[image_path] = []
        images_limits[image_path] = []
        for zoom_level in range(0, zoom_levels):
            north_fix = lat_to_wmerc(north, zoom_level, tile_size)
            south_fix = lat_to_wmerc(south, zoom_level, tile_size)
            west_fix = lon_to_wmerc(west, zoom_level, tile_size)
            east_fix = lon_to_wmerc(east, zoom_level, tile_size)
            north_limit = get_y_tile(north, zoom_level)
            south_limit = get_y_tile(south, zoom_level)
            west_limit = get_x_tile(west, zoom_level)
            east_limit = get_x_tile(east, zoom_level)
            images_bounds[image_path].append((north_fix, south_fix, west_fix, east_fix))
            images_limits[image_path].append((north_limit, south_limit, west_limit, east_limit))
    for zoom_level in range(0, zoom_levels):
        columns = 2 ** zoom_level  # and also rows
        print('Generating scale', zoom_level)
        tile_lon_range = base_lon_range / columns
        tile_lat_range = base_lat_range / columns
        tiles_to_render = []
        for image in images_parsed:
            image_path, north, south, west, east = image
            north_limit, south_limit, west_limit, east_limit = images_limits[image_path][zoom_level]
            for tile_y in range(north_limit, south_limit + 1):
                for tile_x in range(west_limit, east_limit + 1):
                    tile_coord = (tile_x, tile_y)
                    if tile_coord not in tiles_to_render:
                        tiles_to_render.append(tile_coord)
        print(tiles_to_render)
        for tile_coord in tiles_to_render:
            x, y = tile_coord
            tile_bound_x = tile_size * x
            tile_bound_y = tile_size * y
            print('Generating tile', x, ' ', y)
            tile_image = Image.new('RGBA', (tile_size, tile_size), (0, 0, 0, 0))
            tile_dir = "{0}/tileset/{1}/{2}/{3}".format(image_root, tileset_id, zoom_level, x)
            if not os.path.exists(tile_dir):
                os.makedirs(tile_dir)
            for image in images_parsed:
                image_path, north, south, west, east = image
                north_fix = north
                south_fix = south
                west_fix = west
                east_fix = east
                north_fix, south_fix, west_fix, east_fix = images_bounds[image_path][zoom_level]
                north_limit, south_limit, west_limit, east_limit = images_limits[image_path][zoom_level]
                if x >= west_limit and x <= east_limit and y >= north_limit and y <= south_limit:
                    scale_h = (abs(south_fix - north_fix))
                    scale_w = (abs(east_fix - west_fix))
                    paste_x = clamp(floor((west_fix - tile_bound_x)), 0, tile_size - 1)
                    paste_y = clamp(floor((north_fix - tile_bound_y)), 0, tile_size - 1)
                    paste_x_end = clamp(ceil((east_fix - tile_bound_x)), 0, tile_size)
                    paste_y_end = clamp(ceil((south_fix - tile_bound_y)), 0, tile_size)
                    paste_w = paste_x_end - paste_x
                    paste_h = paste_y_end - paste_y
                    if paste_h > 0 and paste_w > 0:
                        image_source = Image.open("{0}/cropped/{1}".format(image_root, image_path))
                        w, h = image_source.size
                        scale_fact_w = w / scale_w
                        scale_fact_h = h / scale_h
                        crop_x = int((clamp(
                            (clamp(west_fix, tile_bound_x, tile_bound_x + tile_size - 1) - west_fix) * scale_fact_w, 0,
                            w - 1)))
                        crop_y = int((clamp(
                            (clamp(north_fix, tile_bound_y, tile_bound_y + tile_size - 1) - north_fix) * scale_fact_h,
                            0, h - 1)))
                        crop_x_end = int((clamp(
                            (clamp(east_fix, tile_bound_x, tile_bound_x + tile_size - 1) - west_fix) * scale_fact_w, 0,
                            w - 1)))
                        crop_y_end = int((clamp(
                            (clamp(south_fix, tile_bound_y, tile_bound_y + tile_size - 1) - north_fix) * scale_fact_h,
                            0, h - 1)))
                        crop_w = int(crop_x_end - crop_x)
                        crop_h = int(crop_y_end - crop_y)
                        paste_x = int(paste_x)
                        paste_y = int(paste_y)
                        paste_h = int(paste_h)
                        paste_w = int(paste_w)
                        image_source_array = image_source.resize((paste_w, paste_h), resample=Image.BICUBIC,
                                                                 box=(crop_x, crop_y, crop_x_end, crop_y_end))
                        tile_image.paste(image_source_array, (int(paste_x), int(paste_y)))
            tile_image.save("{0}/{1}.png".format(tile_dir, y))


loop_stop = False


def main_loop():
    while not loop_stop:
        # processing just uploaded images
        print('Processing raw images...')
        images = []
        connection = make_connection()
        cursor = connection.cursor()
        query = """
SELECT *
FROM images
WHERE status LIKE 'raw'
"""
        cursor.execute(query)
        connection.close()
        for row in cursor:
            images.append(row)
        print('Found ', len(images), ' images.')
        for image in images:
            print('Processing ', image['image_path'], ' (#', image['id'], ')')
            result_code, result_data = autocrop_and_detect(image['image_path'])
            if result_code == 1:
                connection = make_connection()
                cursor = connection.cursor()
                query = 'UPDATE images SET status = \'croperror\' WHERE id = ' + str(image['id'])
                cursor.execute(query)
                connection.commit()
                connection.close()
            else:
                image_bounds = result_data[0]
                point_nwx, point_nwy = image_bounds[0]
                point_nex, point_ney = image_bounds[1]
                point_sex, point_sey = image_bounds[2]
                point_swx, point_swy = image_bounds[3]
                query = """
UPDATE images
SET point_nw_x = {0}, point_nw_y = {1}, point_ne_x = {2}, point_ne_y = {3},
point_se_x = {4}, point_se_y = {5}, point_sw_x = {6}, point_sw_y = {7}
WHERE id = {8}""".format(point_nwx, point_nwy, point_nex, point_ney, point_sex, point_sey, point_swx, point_swy,
                         image['id'])
                connection = make_connection()
                cursor = connection.cursor()
                cursor.execute(query)
                connection.commit()
                connection.close()
                coords_ok = True
                for coords in result_data[1]:
                    if len(coords) != 1:
                        coords_ok = False
                        break
                if not coords_ok:
                    coord_north = '; '.join(result_data[1][0]).replace('\'', '\\\'').replace('"', '\\"')
                    coord_south = '; '.join(result_data[1][1]).replace('\'', '\\\'').replace('"', '\\"')
                    coord_west = '; '.join(result_data[1][2]).replace('\'', '\\\'').replace('"', '\\"')
                    coord_east = '; '.join(result_data[1][3]).replace('\'', '\\\'').replace('"', '\\"')
                    sheet_code = ''
                    query = "INSERT INTO image_problem (image_id, north, south, west, east) VALUES ({0}, '{1}', '{2}', '{3}', '{4}')".format(
                        image['id'], coord_north, coord_south, coord_west, coord_east)
                    connection = make_connection()
                    cursor = connection.cursor()
                    cursor.execute(query)
                    connection.commit()
                    connection.close()
                    connection = make_connection()
                    cursor = connection.cursor()
                    query = 'UPDATE images SET status = \'detecterror\' WHERE id = ' + str(image['id'])
                    cursor.execute(query)
                    connection.commit()
                    connection.close()
                    print('Unable to detect coordinates correctly. Please specify them manually.')
                else:
                    coord_north = result_data[1][0][0].replace('\'', '\\\'').replace('"', '\\"')
                    coord_south = result_data[1][1][0].replace('\'', '\\\'').replace('"', '\\"')
                    coord_west = result_data[1][2][0].replace('\'', '\\\'').replace('"', '\\"')
                    coord_east = result_data[1][3][0].replace('\'', '\\\'').replace('"', '\\"')
                    sheet_code = get_sheet_code(parse_angle_dms(coord_north), parse_angle_dms(coord_south),
                                                parse_angle_dms(coord_west), parse_angle_dms(coord_east))
                    if sheet_code is None:
                        sheet_code = 'n/a'
                    query = """
UPDATE images
SET north = '{0}', south = '{1}', west = '{2}', east = '{3}', sheet_code = '{4}'
WHERE id = {5}""".format(coord_north, coord_south, coord_west, coord_east, sheet_code, image['id'])
                    connection = make_connection()
                    cursor = connection.cursor()
                    cursor.execute(query)
                    connection.commit()
                    connection.close()
                    print('Done.')
        # processing images set for re-crop
        images = []
        print('Processing images set for re-crop...')
        connection = make_connection()
        cursor = connection.cursor()
        query = """
SELECT *
FROM images
WHERE status LIKE 'recrop'
"""
        cursor.execute(query)
        connection.close()
        for row in cursor:
            images.append(row)
        print('Found', len(images), 'images.')
        for image in images:
            print('Processing ', image['image_path'], ' (#', image['id'], ')')
            coords = [(image['point_nw_x'], image['point_nw_y']), (image['point_ne_x'], image['point_ne_y']),
                      (image['point_se_x'], image['point_se_y']), (image['point_sw_x'], image['point_sw_y'])]
            image_crop(image['image_path'], coords)
            connection = make_connection()
            cursor = connection.cursor()
            query = 'UPDATE images SET status = \'ready\' WHERE id = ' + str(image['id'])
            cursor.execute(query)
            connection.commit()
            connection.close()
            sheet_code = get_sheet_code(parse_angle_dms(image['north']), parse_angle_dms(image['south']),
                                        parse_angle_dms(image['west']), parse_angle_dms(image['east']))
            if not sheet_code is None:
                connection = make_connection()
                cursor = connection.cursor()
                query = "UPDATE images SET sheet_code = '{0}' WHERE id = {1}".format(
                    sheet_code.replace("'", "\\'").replace('"', '\\"'), image['id'])
                cursor.execute(query)
                connection.commit()
                connection.close()
            print('Done.')
        time.sleep(loop_timeout)
    print('Bye.')


def main():
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
    loop_thread = threading.Thread(target=main_loop, daemon=True)
    loop_thread.start()
    while True:
        cmd = input('')
        if cmd == 'stop':
            loop_stop = True
            break


if __name__ == "__main__":
    names = ["100k.jpg",  "M-44-44-А-б.jpg", "M-44-44-Б-а.jpg", "M-45-VIII.jpg",
             "N-44-96.jpg", "N-44-107.jpg"]
    name = "100k.jpg"
    # Язык для tesseract.
    os.environ["TESSDATA_PREFIX"] = "/home/kamotora/IdeaProjects/maga/image-processor-py/tesseract_languages"
    res = autocrop_and_detect(name)
    if res[0] == 1:
        print("for " + name + " result is bad(")
    else:
        print("for " + name + " result is GOOD")
    # for name in names:
    #     res = autocrop_and_detect(name)
    #     if res[0] == 1:
    #         print("for " + name + " result is bad(")
    #     else:
    #         print("for " + name + " result is GOOD")
    exit(0)
    # main()
